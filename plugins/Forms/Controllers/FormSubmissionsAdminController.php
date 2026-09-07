<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Forms\Controllers;

use Pubvana\Controllers\Admin\AdminController;

class FormSubmissionsAdminController extends AdminController
{
    private function adminBase(): string
    {
        return '/admin' . rtrim((string) $this->app->pluginLoader()->routePrefix('pubvana/forms'), '/');
    }

    public function index(?string $formId = null): void
    {
        $request = $this->app->request();
        $page = max(1, (int) ($request->query->page ?? 1));
        $queryFormId = $request->query->form_id ?? null;
        $formIdInt = $formId !== null ? (int) $formId : (($queryFormId !== null && $queryFormId !== '') ? (int) $queryFormId : null);

        $result = $this->app->forms()->listSubmissions($page, $formIdInt);

        $this->render('pubvana/forms/admin/submissions', [
            'pageTitle'    => $formIdInt ? 'Form Submissions' : 'Submissions',
            'adminBase'    => $this->adminBase(),
            'submissions'  => $result['items'],
            'total'        => $result['total'],
            'page'         => $result['page'],
            'perPage'      => $result['per_page'],
            'filterFormId' => $formIdInt,
            'forms'        => $this->app->forms()->listAllForms(),
        ]);
    }

    public function show(string $id): void
    {
        $submission = $this->app->forms()->findSubmission((int) $id);
        if ($submission === null) {
            $this->app->session()->flash('error', 'Submission not found.');
            $this->app->redirect($this->adminBase() . '/submissions');
            return;
        }

        $form = $this->app->forms()->findForm((int) $submission->form_id);

        $this->render('pubvana/forms/admin/submission', [
            'pageTitle'  => 'Submission Detail',
            'adminBase'  => $this->adminBase(),
            'submission' => $submission,
            'form'       => $form,
            'payload'    => $this->app->forms()->decodeSubmissionPayload($submission->payload_json ?? null),
        ]);
    }
}
