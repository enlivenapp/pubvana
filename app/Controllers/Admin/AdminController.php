<?php

declare(strict_types=1);

namespace Pubvana\Controllers\Admin;

use flight\Engine;

/**
 * AdminController - Dashboard and base controller for all admin routes.
 *
 * Acts as both the dashboard controller (index method) and the base class
 * for all admin controllers. Provides the render() method that wraps
 * content in the Tabler UI admin layout.
 *
 * @package Pubvana\Controllers\Admin
 */
class AdminController
{
    /** @var Engine<object> The FlightPHP app instance */
    protected Engine $app;
    protected string $configPrepend;

    /**
     * @param Engine<object> $app            The FlightPHP app instance
     * @param string         $configPrepend  Config key prefix for plugin settings
     */
    public function __construct(Engine $app, string $configPrepend = 'pubvana')
    {
        $this->app = $app;
        $this->configPrepend = $configPrepend;
    }

    /**
     * Dashboard — landing page after login.
     *
     * Cards and sections are populated by plugins via $app->adext().
     */
    public function index(): void
    {
        $adext = $this->app->adext();
        $user = $this->app->auth()->user();
        $context = [
            'user'      => $user,
            'site_name' => $this->app->get('CMS.siteName') ?? 'Pubvana',
        ];

        $groups = $this->dashboardGroups();

        $entries = $this->normalizeDashboardEntries(
            $adext->get('admin.dashboard', 'cards', $context),
            'label'
        );
        $entries = array_merge(
            $entries,
            $this->normalizeDashboardEntries(
                $adext->get('admin.dashboard', 'sections', $context),
                'title'
            )
        );

        $grouped = $this->groupDashboardEntries($entries, $groups);

        $this->render('admin/dashboard', [
            'pageTitle' => 'Dashboard',
            'groups'    => $grouped,
        ]);
    }

    /**
     * Central dashboard group definitions.
     *
     * Maps a known group token (declared by contributors via the 'group' key)
     * to its display label and display order. Contributors use coarse tokens
     * ('system', 'people', 'content', 'media', ...); this map controls how
     * those tokens are labeled and ordered on the dashboard.
     *
     * The map is intentionally open: a token that is NOT listed here still
     * renders as its own group (ucfirst() label), appended after all known
     * groups. Known-group ordering and labels are centralized here rather
     * than baked into each plugin.
     *
     * @return array<string, array{label: string, priority: int}>
     */
    protected function dashboardGroups(): array
    {
        return [
            'system'    => ['label' => 'System',    'priority' => 10],
            'people'    => ['label' => 'People',    'priority' => 20],
            'content'   => ['label' => 'Content',   'priority' => 30],
            'media'     => ['label' => 'Media',     'priority' => 40],
            'commerce'  => ['label' => 'Commerce',  'priority' => 50],
            'community' => ['label' => 'Community', 'priority' => 60],
            'analytics' => ['label' => 'Analytics', 'priority' => 70],
            'tools'     => ['label' => 'Tools',     'priority' => 80],
        ];
    }

    /**
     * Flatten adext dashboard contributions into a sorted entries array.
     *
     * @param array<string, array<string, mixed>> $contributors Adext contributions keyed by source
     * @param string               $requiredKey  Key that each card/section must contain
     * @return list<array<string, mixed>>
     */
    protected function normalizeDashboardEntries(array $contributors, string $requiredKey): array
    {
        $entries = [];

        foreach ($contributors as $source => $contribution) {
            $candidates = $this->flattenCandidates($contribution);

            foreach ($candidates as $entry) {
                if (!isset($entry[$requiredKey])) {
                    continue;
                }
                $entry['source'] = $source;
                $entry['group'] = $entry['group'] ?? $this->groupForSource($source);
                $entries[] = $this->normalizeDashboardUrls($entry);
            }
        }

        return $entries;
    }

    /**
     * Group a flat list of dashboard entries, ordered by the group map.
     *
     * Only groups that actually contain entries are included. Unknown group
     * tokens are appended at the end in first-seen order.
     *
     * @param list<array<string, mixed>> $entries Flat list of normalized cards/sections
     * @param array<string, array<string, mixed>> $groups  Group map from dashboardGroups()
     * @return list<array<string, mixed>> Groups in display order, each with id, label, items
     */
    protected function groupDashboardEntries(array $entries, array $groups): array
    {
        // Order groups by the map, then append any unknown groups.
        $order = [];
        foreach ($groups as $token => $def) {
            $order[$token] = (int) ($def['priority'] ?? 50);
        }
        foreach ($entries as $entry) {
            $token = $entry['group'] ?? 'other';
            if (!isset($order[$token])) {
                $order[$token] = ($order === [] ? 0 : max($order)) + 1;
            }
        }

        $result = [];
        foreach ($entries as $entry) {
            $token = $entry['group'] ?? 'other';
            if (!isset($result[$token])) {
                $def = $groups[$token] ?? null;
                $result[$token] = [
                    'id'    => $token,
                    'label' => $def['label'] ?? ucfirst($token),
                    'items' => [],
                ];
            }
            $result[$token]['items'][] = $entry;
        }

        // Sort by the computed order
        uksort($result, fn($a, $b) => ($order[$a] ?? 99) <=> ($order[$b] ?? 99));

        return array_values($result);
    }

    /**
     * Derive a group token from a plugin source key.
     *
     * Used as a fallback when a contributor omits an explicit 'group' key.
     * Maps a plugin namespace (pubvana.blog) to a coarse group token. Unknown
     * namespaces fall through to their own distinct token (the namespace
     * itself) so they self-organize into a standalone group rather than being
     * collapsed under a generic 'other'.
     *
     * @param string $source Contributor key, e.g. 'pubvana.blog'
     * @return string
     */
    protected function groupForSource(string $source): string
    {
        $parts = explode('.', $source);
        $ns = $parts[1] ?? $parts[0];

        return match ($ns) {
            'users', 'groups', 'permissions', 'profiles', 'profile', 'admin' => 'people',
            'blog', 'pages', 'posts', 'categories', 'tags', 'navigation' => 'content',
            'media' => 'media',
            'commerce', 'shop', 'cart', 'products', 'orders' => 'commerce',
            'community', 'forums', 'discussions', 'members' => 'community',
            'analytics', 'stats', 'reports', 'tracking' => 'analytics',
            'tools', 'backups', 'updates', 'seo', 'redirects', 'import', 'export' => 'tools',
            default => $ns !== '' ? $ns : 'other',
        };
    }

    /**
     * Recursively collect flat array candidates from a contribution.
     *
     * Handles flat lists ([card, card]), single envelopes ([card]) and the
     * legacy double-wrap ([[card]]) so no contributor is dropped.
     *
     * @param array<int|string, mixed> $contribution
     * @return list<array<string, mixed>>
     */
    protected function flattenCandidates(array $contribution): array
    {
        $candidates = [];

        foreach ($contribution as $key => $value) {
            if (!is_int($key) || !is_array($value)) {
                continue;
            }
            if (isset($value['label']) || isset($value['title']) || isset($value['id'])) {
                $candidates[] = $value;
            } else {
                $candidates = array_merge($candidates, $this->flattenCandidates($value));
            }
        }

        return $candidates;
    }

    /**
     * Prepend /admin to relative URLs in a dashboard card or section.
     *
     * @param array<string, mixed> $entry Single dashboard card or section
     * @return array<string, mixed>
     */
    protected function normalizeDashboardUrls(array $entry): array
    {
        $prefix = static fn(string $url): string =>
            str_starts_with($url, '/admin') ? $url : '/admin' . $url;

        foreach (['href'] as $field) {
            if (!empty($entry[$field]) && is_string($entry[$field]) && str_starts_with($entry[$field], '/')) {
                $entry[$field] = $prefix($entry[$field]);
            }
        }

        if (!empty($entry['items']) && is_array($entry['items'])) {
            foreach ($entry['items'] as $index => $item) {
                if (isset($item['href']) && is_string($item['href']) && str_starts_with($item['href'], '/')) {
                    $entry['items'][$index]['href'] = $prefix($item['href']);
                }
            }
        }

        return $entry;
    }

    /**
     * Render an admin view wrapped in the Tabler UI layout.
     *
     * Set $layout to false for HTMX partial responses (no full page wrapper).
     *
     * @param array<string, mixed> $data
     */
    protected function render(string $view, array $data = [], bool $layout = true): void
    {
        if (!$layout) {
            $this->app->render($view, $data);
            return;
        }

        $content = $this->app->view()->fetch($view, $data);

        $user = null;
        $userGroups = '';
        try {
            $user = $this->app->auth()->user();
        } catch (\Throwable $e) {
            // Auth not available (e.g. during CLI)
        }
        if ($user !== null) {
            $groups = $user->getGroups();
            $userGroups = implode(', ', $groups);
        }

        $adext = $this->app->adext();

        $this->app->render('admin/layouts/admin', [
            'content'    => $content,
            'pageTitle'  => $data['pageTitle'] ?? 'Dashboard',
            'siteName'   => $this->app->get('CMS.siteName') ?? 'Pubvana',
            'user'       => $user,
            'userGroups' => $userGroups,
            'menuSlots'  => [
                'content'    => $adext->get('admin.menu', 'content'),
                'appearance' => $adext->get('admin.menu', 'appearance'),
                'tools'      => $adext->get('admin.menu', 'tools'),
                'settings'   => $adext->get('admin.menu', 'settings'),
                'plugins'    => $adext->get('admin.menu', 'plugins'),
            ],
        ]);
    }

    /**
     * Get a config value for this plugin.
     */
    protected function getConfig(string $key, mixed $default = null): mixed
    {
        return $this->app->get($this->configPrepend . '.' . $key) ?? $default;
    }
}
