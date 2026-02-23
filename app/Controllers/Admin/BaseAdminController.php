<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

abstract class BaseAdminController extends BaseController
{
    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);

        if (! auth()->loggedIn() || ! auth()->user()->can('admin.access')) {
            redirect()->to('/login')->with('error', 'You must be logged in to access the admin panel.')->send();
            exit;
        }
    }

    protected function baseData(string $title, string $activeNav = ''): array
    {
        return array_merge($this->data, [
            'page_title' => $title . ' — Pubvana Admin',
            'active_nav' => $activeNav,
            'user'       => auth()->user(),
        ]);
    }

    protected function adminView(string $view, array $data = []): string
    {
        return view('admin/' . $view, $data);
    }
}
