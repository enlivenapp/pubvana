<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Forms\Controllers;

use Pubvana\Controllers\Admin\AdminController;

class FormsAdminController extends AdminController
{
    private function adminBase(): string
    {
        return '/admin' . rtrim((string) $this->app->pluginLoader()->routePrefix('pubvana/forms'), '/');
    }

    public function index(): void
    {
        $request = $this->app->request();
        $page = max(1, (int) ($request->query->page ?? 1));
        $result = $this->app->forms()->listForms($page);

        $this->render('pubvana/forms/admin/index', [
            'pageTitle' => 'Forms',
            'adminBase' => $this->adminBase(),
            'forms'     => $result['items'],
            'total'     => $result['total'],
            'page'      => $result['page'],
            'perPage'   => $result['per_page'],
        ]);
    }

    public function create(): void
    {
        $this->render('pubvana/forms/admin/create', [
            'pageTitle'  => 'New Form',
            'adminBase'  => $this->adminBase(),
            'fieldsJson' => json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        ]);
    }

    public function store(): void
    {
        $post = $this->app->request()->data->getData();
        unset($post['_csrf_token']);

        $slug = $this->app->slugify($post['slug'] ?? '' ?: $post['name'] ?? '');

        if ($slug === '') {
            $this->app->session()->flash('error', 'A slug is required.');
            $this->app->redirect($this->adminBase() . '/create');
            return;
        }

        if ($this->app->forms()->slugExists($slug)) {
            $this->app->session()->flash('error', 'A form with that slug already exists.');
            $this->app->redirect($this->adminBase() . '/create');
            return;
        }

        $this->app->forms()->createForm([
            'name'                => $post['name'] ?? '',
            'slug'                => $slug,
            'description'         => $post['description'] ?? null,
            'status'              => $post['status'] ?? 'draft',
            'submit_label'        => $post['submit_label'] ?? 'Submit',
            'success_message'     => $post['success_message'] ?? 'Thanks, your submission has been received.',
            'notification_emails' => $post['notification_emails'] ?? null,
            'field_definitions'   => $post['field_definitions'] ?? '[]',
        ]);

        $this->app->session()->flash('success', 'Form created.');
        $this->app->redirect($this->adminBase());
    }

    public function edit(string $id): void
    {
        $form = $this->app->forms()->findForm((int) $id);

        if ($form === null) {
            $this->app->session()->flash('error', 'Form not found.');
            $this->app->redirect($this->adminBase());
            return;
        }

        $this->render('pubvana/forms/admin/edit', [
            'pageTitle'  => 'Edit Form',
            'adminBase'  => $this->adminBase(),
            'form'       => $form,
            'fieldsJson' => json_encode($this->app->forms()->getFieldDefinitions((int) $form->id), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        ]);
    }

    public function update(string $id): void
    {
        $post = $this->app->request()->data->getData();
        unset($post['_csrf_token']);

        $form = $this->app->forms()->findForm((int) $id);
        if ($form === null) {
            $this->app->session()->flash('error', 'Form not found.');
            $this->app->redirect($this->adminBase());
            return;
        }

        $this->app->forms()->updateForm((int) $id, [
            'name'                => $post['name'] ?? '',
            'description'         => $post['description'] ?? null,
            'status'              => $post['status'] ?? 'draft',
            'submit_label'        => $post['submit_label'] ?? 'Submit',
            'success_message'     => $post['success_message'] ?? 'Thanks, your submission has been received.',
            'notification_emails' => $post['notification_emails'] ?? null,
            'field_definitions'   => $post['field_definitions'] ?? '[]',
        ]);

        $this->app->session()->flash('success', 'Form updated.');
        $this->app->redirect($this->adminBase() . '/' . $id . '/edit');
    }

    public function delete(string $id): void
    {
        if ($this->app->forms()->deleteForm((int) $id)) {
            $this->app->session()->flash('success', 'Form deleted.');
        } else {
            $this->app->session()->flash('error', 'Form not found.');
        }
        $this->app->redirect($this->adminBase());
    }
}
