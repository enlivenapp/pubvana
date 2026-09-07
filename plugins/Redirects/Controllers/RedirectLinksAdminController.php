<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Redirects\Controllers;

use Pubvana\Controllers\Admin\AdminController;

/**
 * RedirectLinksAdminController - Admin manager for tracked incoming 404s.
 */
class RedirectLinksAdminController extends AdminController
{
    /**
     * List incoming 404s filtered by status.
     */
    public function index(): void
    {
        $status = (string) ($this->app->request()->query->status ?? 'active');
        if (!in_array($status, ['active', 'ignored', 'resolved', 'all'], true)) {
            $status = 'active';
        }

        $this->render('pubvana/redirects/admin/incoming-404s', [
            'pageTitle' => '404 Manager',
            'entries'   => $this->app->redirectLinks()->all($status),
            'status'    => $status,
            'adminBase' => $this->adminBase(),
        ]);
    }

    /**
     * Mark an incoming 404 entry as ignored.
     */
    public function ignore(string $id): void
    {
        if ($this->app->redirectLinks()->setIgnored((int) $id, true) === null) {
            $this->app->session()->flash('error', 'Entry not found.');
        } else {
            $this->app->session()->flash('success', 'Entry ignored.');
        }
        $this->app->redirect($this->adminBase() . '/404-manager');
    }

    /**
     * Remove the ignored flag from an incoming 404 entry.
     */
    public function unignore(string $id): void
    {
        if ($this->app->redirectLinks()->setIgnored((int) $id, false) === null) {
            $this->app->session()->flash('error', 'Entry not found.');
        } else {
            $this->app->session()->flash('success', 'Entry unignored.');
        }
        $this->app->redirect($this->adminBase() . '/404-manager?status=ignored');
    }

    /**
     * Delete an incoming 404 entry.
     */
    public function delete(string $id): void
    {
        if ($this->app->redirectLinks()->delete((int) $id)) {
            $this->app->session()->flash('success', 'Entry deleted.');
        } else {
            $this->app->session()->flash('error', 'Entry not found.');
        }
        $this->app->redirect($this->adminBase() . '/404-manager');
    }

    /**
     * Full admin URL base for this plugin (adext prepends '/admin' to the
     * registered route path).
     */
    private function adminBase(): string
    {
        return '/admin' . rtrim((string) $this->app->pluginLoader()->routePrefix('pubvana/redirects'), '/');
    }
}
