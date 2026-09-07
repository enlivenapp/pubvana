<?php

declare(strict_types=1);

namespace Pubvana\Plugins\AiAssistant\Controllers;

use Enlivenapp\FlightShield\Models\User;
use Pubvana\Controllers\Admin\AdminController;

/**
 * AiAdminController - Admin management of API keys, grants, and the
 * default author for the AI Assistant plugin.
 *
 * Every action gates on the seeded 'ai.manage' permission (applied as a
 * route middleware in Plugin::register). Each mutation flashes a message
 * and redirects back to the plugin's admin base (see adminBase()).
 *
 * @package Pubvana\Plugins\AiAssistant\Controllers
 */
class AiAdminController extends AdminController
{
    public function __construct(\flight\Engine $app)
    {
        parent::__construct($app, 'pubvana.ai');
    }

    /**
     * Admin URL base for this plugin (adext prepends /admin to routes).
     */
    private function adminBase(): string
    {
        return '/admin' . rtrim((string) $this->app->pluginLoader()->routePrefix('pubvana/ai'), '/');
    }

    /**
     * Keys, grants, audit log, and the default-author control.
     */
    public function manage(): void
    {
        $logLimit = max(1, (int) $this->getConfig('log_limit', 200));

        $siteUrl = trim((string) ($this->app->settings()->get('CMS.siteUrl') ?? ''));
        if ($siteUrl === '') {
            $siteUrl = $this->app->request()->getBaseUrl();
        }

        $this->render('pubvana/ai/admin/manage', [
            'pageTitle'       => 'AI Assistant',
            'adminBase'       => $this->adminBase(),
            'keys'            => $this->app->ai()->listKeys(),
            'helpGroups'      => $this->app->ai()->helpGroups(),
            'logLimit'        => $logLimit,
            'logs'            => $this->app->ai()->recentLogs($logLimit),
            'defaultAuthorId' => $this->app->ai()->defaultAuthorId(),
            'activeUsers'     => (new User($this->app->db()))->findActive(),
            'siteUrl'         => rtrim($siteUrl, '/'),
        ]);
    }

    /**
     * Create a key and reveal the plaintext token once.
     */
    public function createKey(): void
    {
        $name = (string) ($this->app->request()->data->name ?? '');

        $result = $this->app->ai()->createKey($name);

        $this->app->session()->flash('success', 'API key created. Copy it now; it is shown only once:');
        $this->app->session()->flash('plain_token', $result['plain']);
        $this->app->redirect($this->adminBase() . '/manage');
    }

    /**
     * Replace a key's entire grant set from submitted checkboxes.
     */
    public function updateGrants(string $id): void
    {
        $keyId = (int) $id;
        $data = $this->app->request()->data->getData();

        $permissions = [];
        if (!empty($data['grants']) && is_array($data['grants'])) {
            foreach ($data['grants'] as $permission) {
                $permissions[] = (string) $permission;
            }
        }

        if ($this->app->ai()->updateGrants($keyId, $permissions)) {
            $this->app->session()->flash('success', 'Key grants updated.');
        } else {
            $this->app->session()->flash('error', 'API key not found.');
        }
        $this->app->redirect($this->adminBase() . '/manage');
    }

    /**
     * Enable or disable a key.
     */
    public function toggleKey(string $id): void
    {
        if ($this->app->ai()->toggle((int) $id)) {
            $this->app->session()->flash('success', 'API key state changed.');
        } else {
            $this->app->session()->flash('error', 'API key not found.');
        }
        $this->app->redirect($this->adminBase() . '/manage');
    }

    /**
     * Delete a key and its grants.
     */
    public function deleteKey(string $id): void
    {
        if ($this->app->ai()->deleteKey((int) $id)) {
            $this->app->session()->flash('success', 'API key deleted.');
        } else {
            $this->app->session()->flash('error', 'API key not found.');
        }
        $this->app->redirect($this->adminBase() . '/manage');
    }

    /**
     * Set the user that AI-created posts and pages are attributed to.
     */
    public function saveAuthor(): void
    {
        $authorId = (int) ($this->app->request()->data->author_id ?? 0);

        $valid = false;
        foreach ((new User($this->app->db()))->findActive() as $user) {
            if ((int) $user->id === $authorId) {
                $valid = true;
                break;
            }
        }

        if ($valid) {
            $this->app->ai()->setDefaultAuthorId($authorId);
            $this->app->session()->flash('success', 'Default author updated.');
        } else {
            $this->app->session()->flash('error', 'Please choose a valid active user.');
        }
        $this->app->redirect($this->adminBase() . '/manage');
    }

    /**
     * Static help page: the grant catalog, endpoint table, and envelope.
     */
    public function help(): void
    {
        $this->render('pubvana/ai/admin/help', [
            'pageTitle'  => 'AI Assistant · Help',
            'adminBase'  => $this->adminBase(),
            'helpGroups' => $this->app->ai()->helpGroups(),
        ]);
    }
}