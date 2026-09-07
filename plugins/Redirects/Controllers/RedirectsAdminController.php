<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Redirects\Controllers;

use Pubvana\Controllers\Admin\AdminController;

/**
 * RedirectsAdminController - Admin CRUD for URL redirects.
 */
class RedirectsAdminController extends AdminController
{
    /**
     * List all redirects.
     */
    public function index(): void
    {
        $this->render('pubvana/redirects/admin/index', [
            'pageTitle' => 'Redirects',
            'redirects' => $this->app->redirects()->all(),
            'adminBase' => $this->adminBase(),
        ]);
    }

    /**
     * Show the create redirect form.
     */
    public function create(): void
    {
        $request = $this->app->request();

        $this->render('pubvana/redirects/admin/create', [
            'pageTitle'         => 'New Redirect',
            'prefillSourcePath' => (string) ($request->query->source_path ?? ''),
            'incoming404Id'     => (int) ($request->query->incoming_404_id ?? 0),
            'targetSuggestions' => $this->app->redirects()->getTargetSuggestions(),
            'adminBase'         => $this->adminBase(),
        ]);
    }

    /**
     * Store a new redirect from POST data.
     */
    public function store(): void
    {
        $post = $this->app->request()->data->getData();
        unset($post['_csrf_token']);

        $redirect = $this->app->redirects()->create($post);
        if (!empty($post['incoming_404_id'])) {
            $this->app->redirectLinks()->markResolved((int) $post['incoming_404_id'], (int) $redirect->id);
        }

        $this->app->session()->flash('success', 'Redirect created.');
        $this->app->redirect($this->adminBase());
    }

    /**
     * Show the edit form for a redirect.
     */
    public function edit(string $id): void
    {
        $redirect = $this->app->redirects()->find((int) $id);
        if ($redirect === null) {
            $this->app->session()->flash('error', 'Redirect not found.');
            $this->app->redirect($this->adminBase());
            return;
        }

        $this->render('pubvana/redirects/admin/edit', [
            'pageTitle'         => 'Edit Redirect',
            'redirect'          => $redirect,
            'targetSuggestions' => $this->app->redirects()->getTargetSuggestions(),
            'adminBase'         => $this->adminBase(),
        ]);
    }

    /**
     * Update an existing redirect from POST data.
     */
    public function update(string $id): void
    {
        $post = $this->app->request()->data->getData();
        unset($post['_csrf_token']);

        if ($this->app->redirects()->update((int) $id, $post) === null) {
            $this->app->session()->flash('error', 'Redirect not found.');
            $this->app->redirect($this->adminBase());
            return;
        }

        $this->app->session()->flash('success', 'Redirect updated.');
        $this->app->redirect($this->adminBase() . '/' . $id . '/edit');
    }

    /**
     * Delete a redirect.
     */
    public function delete(string $id): void
    {
        if ($this->app->redirects()->delete((int) $id)) {
            $this->app->session()->flash('success', 'Redirect deleted.');
        } else {
            $this->app->session()->flash('error', 'Redirect not found.');
        }
        $this->app->redirect($this->adminBase());
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
