<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Analytics\Controllers;

use Pubvana\Controllers\Admin\AdminController;

/**
 * AnalyticsAdminController - Site traffic reports.
 *
 * @package Pubvana\Plugins\Analytics\Controllers
 */
class AnalyticsAdminController extends AdminController
{
    /**
     * Render the analytics report.
     */
    public function index(): void
    {
        $range = $this->validRange($this->app->request()->query->range ?? null);

        $this->render('pubvana/analytics/admin/index', [
            'pageTitle'       => 'Analytics',
            'range'           => $range,
            'report'          => $this->app->analytics()->dashboard($range),
            'trackingEnabled' => $this->app->analytics()->isTrackingEnabled(),
            'adminBase'       => $this->adminBase(),
        ]);
    }

    /**
     * JSON payload for the AJAX range refresh.
     */
    public function data(): void
    {
        $range = $this->validRange($this->app->request()->query->range ?? null);
        $this->app->json($this->app->analytics()->dashboard($range));
    }

    /**
     * Toggle server-side tracking on or off.
     */
    public function toggleTracking(): void
    {
        $enabled = (bool) ($this->app->request()->data->tracking_enabled ?? false);
        $this->app->settings()->set('Analytics.tracking_enabled', $enabled);

        $this->app->session()->flash(
            'success',
            $enabled ? 'Analytics tracking enabled.' : 'Analytics tracking disabled.'
        );
        $this->app->redirect($this->adminBase());
    }

    /**
     * Full admin URL base for this plugin (adext prepends '/admin' to the
     * registered route path).
     */
    private function adminBase(): string
    {
        return '/admin' . rtrim((string) $this->app->pluginLoader()->routePrefix('pubvana/analytics'), '/');
    }

    private function validRange(mixed $raw): string
    {
        return $this->app->analytics()->normalizeRange((string) ($raw ?? '30'));
    }
}