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

    /**
     * Gate a controller action behind the Premium Core licence.
     * On dev domains this is always a no-op. On production it redirects
     * to the Settings → Premium tab when no valid licence is found.
     */
    protected function requirePremium(): void
    {
        $premium = new \App\Services\PremiumService();
        if (! $premium->isLicensed()) {
            redirect()
                ->to('/admin/settings#premium')
                ->with('error', 'This feature requires a Pubvana Premium Core licence. Please add your licence key below.')
                ->send();
            exit;
        }
    }
}
