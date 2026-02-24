<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class MaintenanceFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! setting('App.maintenanceMode')) {
            return;
        }

        // Allow logged-in admins through
        if (auth()->loggedIn() && auth()->user()->can('admin.access')) {
            return;
        }

        return response()
            ->setStatusCode(503)
            ->setBody(view('maintenance'));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nothing
    }
}
