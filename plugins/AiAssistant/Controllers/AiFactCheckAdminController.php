<?php

declare(strict_types=1);

namespace Pubvana\Plugins\AiAssistant\Controllers;

use Pubvana\Controllers\Admin\AdminController;

/**
 * AiFactCheckAdminController - Admin screen for the Fact Checking
 * feature: terms acceptance, the on/off toggle, and the report history.
 *
 * Every action gates on the seeded 'ai.manage' permission (applied as a
 * route middleware in Plugin::register). Each mutation flashes a message
 * and redirects back to the plugin's admin base (see adminBase()).
 *
 * @package Pubvana\Plugins\AiAssistant\Controllers
 */
class AiFactCheckAdminController extends AdminController
{
    public function __construct(\flight\Engine $app)
    {
        parent::__construct($app, 'pubvana.ai');
    }

    /**
     * Admin URL base for this plugin (adext prepends /admin to routes).
     */
    private function adminBase(): string
    {
        return '/admin' . rtrim((string) $this->app->pluginLoader()->routePrefix('pubvana/ai'), '/');
    }

    /**
     * Status card (terms, toggle, key check) and the report history.
     */
    public function index(): void
    {
        $factCheck = $this->app->aiFactCheck();
        $prompt = $factCheck->currentPrompt();

        $reports = [];
        foreach ($factCheck->recentReports(100) as $report) {
            $reports[] = $factCheck->serializeReport($report);
        }

        $this->render('pubvana/ai/admin/fact-checks', [
            'pageTitle'      => 'AI Assistant · Fact Checking',
            'adminBase'      => $this->adminBase(),
            'prompt'         => $prompt,
            'enabled'        => $factCheck->isEnabled(),
            'acceptedAt'     => $factCheck->acceptedAt(),
            'acceptedVersion' => $factCheck->acceptedVersion(),
            'termsCurrent'   => $factCheck->termsCurrent(),
            'blockers'       => $factCheck->enableBlockers(),
            'reports'        => $reports,
            'total'          => $factCheck->countReports(),
            'verdictTones'   => [
                'supported'           => 'success',
                'partially_supported' => 'warning',
                'refuted'             => 'danger',
                'unverifiable'        => 'secondary',
            ],
        ]);
    }

    /**
     * Store acceptance of the current prompt's terms.
     */
    public function acceptTerms(): void
    {
        $data = $this->app->request()->data;

        if (empty($data->agree)) {
            $this->app->session()->flash('error', 'You must tick the agreement box to accept the terms.');
            $this->app->redirect($this->adminBase() . '/fact-checks');
            return;
        }

        $this->app->aiFactCheck()->acceptTerms();
        $this->app->session()->flash('success', 'Terms accepted. Fact checking can now be switched on.');
        $this->app->redirect($this->adminBase() . '/fact-checks');
    }

    /**
     * Switch the service on or off.
     */
    public function toggle(): void
    {
        $factCheck = $this->app->aiFactCheck();
        $turnOn = (int) ($this->app->request()->data->enable ?? 0) === 1;

        if ($turnOn) {
            $blockers = $factCheck->enableBlockers();
            if (!$factCheck->setEnabled(true)) {
                $this->app->session()->flash('error', 'Fact checking could not be enabled: ' . implode(' ', $blockers));
            } else {
                $this->app->session()->flash('success', 'Fact checking is on. Every enabled API key can now read and submit fact checks.');
            }
        } else {
            $factCheck->setEnabled(false);
            $this->app->session()->flash('success', 'Fact checking is off. Fact-check endpoints now refuse every request.');
        }

        $this->app->redirect($this->adminBase() . '/fact-checks');
    }

    /**
     * One full report.
     */
    public function show(string $id): void
    {
        $report = $this->app->aiFactCheck()->findReport((int) $id);
        if ($report === null) {
            $this->app->session()->flash('error', 'Fact-check report not found.');
            $this->app->redirect($this->adminBase() . '/fact-checks');
            return;
        }

        $this->render('pubvana/ai/admin/fact-check-detail', [
            'pageTitle' => 'AI Assistant · Fact Check Report',
            'adminBase' => $this->adminBase(),
            'report'    => $this->app->aiFactCheck()->serializeReport($report),
        ]);
    }

    /**
     * Delete one report from the history.
     */
    public function delete(string $id): void
    {
        if ($this->app->aiFactCheck()->deleteReport((int) $id)) {
            $this->app->session()->flash('success', 'Fact-check report deleted.');
        } else {
            $this->app->session()->flash('error', 'Fact-check report not found.');
        }
        $this->app->redirect($this->adminBase() . '/fact-checks');
    }
}
