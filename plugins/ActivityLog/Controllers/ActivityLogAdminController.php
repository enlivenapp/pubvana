<?php

declare(strict_types=1);

namespace Pubvana\Plugins\ActivityLog\Controllers;

use Pubvana\Controllers\Admin\AdminController;
use Enlivenapp\FlightShield\Middlewares\PermissionMiddleware;

/**
 * ActivityLogAdminController - Admin UI for activity log.
 */
class ActivityLogAdminController extends AdminController
{
    public function index(): void
    {
        $request = $this->app->request();
        $query = $request->query->getData();

        $filters = [
            'user_id'     => $query['user_id'] ?? '',
            'action'      => $query['action'] ?? '',
            'entity_type' => $query['entity_type'] ?? '',
            'entity_name' => $query['entity_name'] ?? '',
            'date_from'   => $query['date_from'] ?? '',
            'date_to'     => $query['date_to'] ?? '',
        ];

        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = 25;

        $logs = $this->app->activityLog()->list($filters, $page, $perPage);
        $total = $this->app->activityLog()->count($filters);
        $totalPages = (int) ceil($total / $perPage);

        $actions = $this->app->activityLog()->getActions();
        $entityTypes = $this->app->activityLog()->getEntityTypes();
        $users = $this->app->activityLog()->getUsers();

        $this->render('pubvana/activity-log/admin/index', [
            'pageTitle'    => 'Activity Log',
            'logs'         => $logs,
            'filters'      => $filters,
            'actions'      => $actions,
            'entityTypes'  => $entityTypes,
            'users'        => $users,
            'page'         => $page,
            'perPage'      => $perPage,
            'total'        => $total,
            'totalPages'   => $totalPages,
            'adminBase'    => $this->adminBase(),
        ]);
    }

    /**
     * Full admin URL base for this plugin (adext prepends '/admin' to the
     * registered route path).
     */
    private function adminBase(): string
    {
        return '/admin' . rtrim((string) $this->app->pluginLoader()->routePrefix('pubvana/activity-log'), '/');
    }
}