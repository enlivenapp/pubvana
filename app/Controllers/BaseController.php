<?php

namespace App\Controllers;

use App\Models\NavigationModel;
use App\Models\SocialModel;
use App\Services\ThemeService;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class BaseController extends Controller
{
    protected array $data = [];
    protected ThemeService $themeService;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->themeService = new ThemeService();

        $this->data['theme']        = $this->themeService->getActive();
        $this->data['site_name']    = site_name();
        $this->data['site_tagline'] = site_tagline();
        $this->data['settings']     = [];

        try {
            $navModel = new NavigationModel();
            $this->data['primary_nav'] = $navModel->where('nav_group', 'primary')->orderBy('sort_order')->findAll();
            $this->data['footer_nav']  = $navModel->where('nav_group', 'footer')->orderBy('sort_order')->findAll();

            $socialModel = new SocialModel();
            $this->data['social_links'] = $socialModel->where('is_active', 1)->orderBy('sort_order')->findAll();
        } catch (\Throwable $e) {
            $this->data['primary_nav']  = [];
            $this->data['footer_nav']   = [];
            $this->data['social_links'] = [];
        }
    }
}
