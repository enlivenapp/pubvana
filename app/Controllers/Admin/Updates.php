<?php

namespace App\Controllers\Admin;

use App\Services\UpdateService;

class Updates extends BaseAdminController
{
    protected UpdateService $updateService;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        $this->updateService = new UpdateService();
    }

    public function index(): string
    {
        $update = $this->updateService->checkForUpdate();

        return $this->adminView('updates/index', array_merge(
            $this->baseData('Updates', 'updates'),
            ['update' => $update]
        ));
    }

    public function check()
    {
        $this->updateService->clearCache();
        return redirect()->to(base_url('admin/updates'))->with('success', 'Update cache cleared — re-checking now.');
    }
}
