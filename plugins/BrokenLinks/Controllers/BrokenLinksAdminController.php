<?php

declare(strict_types=1);

namespace Pubvana\Plugins\BrokenLinks\Controllers;

use Pubvana\Controllers\Admin\AdminController;

/**
 * BrokenLinksAdminController - Admin UI for outbound broken link scanning.
 */
class BrokenLinksAdminController extends AdminController
{
    /**
     * List broken link results grouped by source.
     */
    public function index(): void
    {
        $showDismissed = (bool) ($this->app->request()->query->dismissed ?? false);

        $this->render('pubvana/brokenlinks/admin/index', [
            'pageTitle'      => 'Broken Links',
            'grouped'        => $this->app->brokenLinks()->all($showDismissed),
            'total'          => $this->app->brokenLinks()->countBroken(),
            'showDismissed'  => $showDismissed,
            'adminBase'      => $this->adminBase(),
        ]);
    }

    /**
     * Full admin URL base for this plugin (adext prepends '/admin' to the
     * registered route path).
     */
    private function adminBase(): string
    {
        return '/admin' . rtrim((string) $this->app->pluginLoader()->routePrefix('pubvana/brokenlinks'), '/');
    }

    /**
     * Run a full scan of all registered content sources.
     */
    public function scan(): void
    {
        $result = $this->app->brokenLinks()->scan();

        $this->app->session()->flash(
            'success',
            sprintf(
                'Scan complete: checked %d link%s across %d source%s. %d broken.',
                $result['total'],
                $result['total'] !== 1 ? 's' : '',
                $result['sources'],
                $result['sources'] !== 1 ? 's' : '',
                $result['broken']
            )
        );

        $this->app->redirect($this->adminBase());
    }

    /**
     * Recheck a single broken link.
     */
    public function recheck(string $id): void
    {
        $result = $this->app->brokenLinks()->recheck((int) $id);

        if ($result['error'] === 'Entry not found.') {
            $this->app->session()->flash('error', 'Entry not found.');
            $this->app->redirect($this->adminBase());
            return;
        }

        if ($this->app->brokenLinks()->isOk($result['status'])) {
            $this->app->session()->flash('success', 'Link is now reachable and has been removed.');
        } else {
            $label = $result['status'] !== null ? (string) $result['status'] : 'unreachable';
            $this->app->session()->flash('error', 'Link is still broken (status: ' . $label . ').');
        }

        $this->app->redirect($this->adminBase());
    }

    /**
     * Permanently dismiss a broken link entry.
     */
    public function dismiss(string $id): void
    {
        if ($this->app->brokenLinks()->dismiss((int) $id) === null) {
            $this->app->session()->flash('error', 'Entry not found.');
        } else {
            $this->app->session()->flash('success', 'Entry dismissed permanently.');
        }

        $this->app->redirect($this->adminBase());
    }
}
