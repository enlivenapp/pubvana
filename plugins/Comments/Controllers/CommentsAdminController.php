<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Comments\Controllers;

use Pubvana\Controllers\Admin\AdminController;

/**
 * CommentsAdminController - Comment moderation and configuration.
 *
 * Manages the moderation queue and the combined settings + host-manager
 * page (config fields plus an enable/disable toggle for each registered
 * comment host, mirroring the Search source manager).
 *
 * @package Pubvana\Plugins\Comments\Controllers
 */
class CommentsAdminController extends AdminController
{
    /**
     * Moderation list, filterable by status, paginated.
     */
    public function index(): void
    {
        $request = $this->app->request();
        $status  = $request->query->status ?? null;
        if ($status !== null && !in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $status = null;
        }
        $page    = max(1, (int) ($request->query->page ?? 1));
        $perPage = 25;

        $service = $this->app->comments();
        $list    = $service->list($page, $perPage, $status);

        $counts = [
            'all'      => $service->countByStatus(),
            'pending'  => $service->countByStatus('pending'),
            'approved' => $service->countByStatus('approved'),
            'rejected' => $service->countByStatus('rejected'),
        ];

        $totalForStatus = $status !== null ? $counts[$status] : $counts['all'];
        $totalPages     = (int) ceil($totalForStatus / $perPage);

        $this->render('pubvana/comments/admin/index', [
            'pageTitle'  => 'Comments',
            'comments'   => $list,
            'counts'     => $counts,
            'status'     => $status,
            'page'       => $page,
            'totalPages' => $totalPages,
            'adminBase'  => $this->adminBase(),
        ]);
    }

    /**
     * Single comment detail with moderation actions.
     */
    public function show(string $id): void
    {
        $comment = $this->app->comments()->find((int) $id);

        if ($comment === null) {
            $this->app->session()->flash('error', 'Comment not found.');
            $this->app->redirect($this->adminBase());
            return;
        }

        $this->render('pubvana/comments/admin/show', [
            'pageTitle' => 'Comment #' . $id,
            'comment'   => $comment,
            'hostItem'  => $this->app->comments()->hostItem((string) $comment->commentable_type, (int) $comment->commentable_id),
            'adminBase' => $this->adminBase(),
        ]);
    }

    /**
     * Approve a comment.
     */
    public function approve(string $id): void
    {
        $comment = $this->app->comments()->approve((int) $id);
        if ($comment === null) {
            $this->app->session()->flash('error', 'Comment not found.');
        } else {
            $this->app->session()->flash('success', 'Comment approved.');
        }
        $this->app->redirect($this->adminBase());
    }

    /**
     * Reject a comment.
     */
    public function reject(string $id): void
    {
        $comment = $this->app->comments()->reject((int) $id);
        if ($comment === null) {
            $this->app->session()->flash('error', 'Comment not found.');
        } else {
            $this->app->session()->flash('success', 'Comment rejected.');
        }
        $this->app->redirect($this->adminBase());
    }

    /**
     * Delete a comment permanently.
     */
    public function delete(string $id): void
    {
        if ($this->app->comments()->delete((int) $id)) {
            $this->app->session()->flash('success', 'Comment deleted.');
        } else {
            $this->app->session()->flash('error', 'Comment not found.');
        }
        $this->app->redirect($this->adminBase());
    }

    /**
     * Comments settings + host manager page.
     */
    public function settingsIndex(): void
    {
        $service = $this->app->comments();

        $this->render('pubvana/comments/admin/settings', [
            'pageTitle'         => 'Comment Settings',
            'enabled'           => (bool) $service->setting('comments_enabled', true),
            'guestComments'     => (bool) $service->setting('allow_guest_comments', false),
            'defaultStatus'     => (string) $service->setting('default_status', 'pending'),
            'maxNestingDepth'   => (int) $service->setting('max_nesting_depth', 3),
            'captchaProvider'   => (string) $service->setting('captcha_provider', 'none'),
            'captchaSiteKey'    => (string) $service->setting('captcha_site_key', ''),
            'captchaSecretKey'  => (string) $service->setting('captcha_secret_key', ''),
            'hosts'             => $service->enabledHosts(true),
            'hostCounts'        => $service->countsByHost(),
            'adminBase'         => $this->adminBase(),
        ]);
    }

    /**
     * Persist the comment config fields and per-host toggles.
     */
    public function settingsSave(): void
    {
        $data    = $this->app->request()->data->getData();
        $service = $this->app->comments();

        $this->app->settings()->set('Comments.comments_enabled', $data['comments_enabled'] ?? 0 ? '1' : '0');
        $this->app->settings()->set('Comments.allow_guest_comments', $data['allow_guest_comments'] ?? 0 ? '1' : '0');

        $defaultStatus = (string) ($data['default_status'] ?? 'pending');
        if (!in_array($defaultStatus, ['pending', 'approved'], true)) {
            $defaultStatus = 'pending';
        }
        $this->app->settings()->set('Comments.default_status', $defaultStatus);

        $this->app->settings()->set('Comments.max_nesting_depth', (string) max(1, (int) ($data['max_nesting_depth'] ?? 3)));

        $captchaProvider = (string) ($data['captcha_provider'] ?? 'none');
        if (!in_array($captchaProvider, ['none', 'hcaptcha', 'recaptcha'], true)) {
            $captchaProvider = 'none';
        }
        $this->app->settings()->set('Comments.captcha_provider', $captchaProvider);
        $this->app->settings()->set('Comments.captcha_site_key', (string) ($data['captcha_site_key'] ?? ''));
        $this->app->settings()->set('Comments.captcha_secret_key', (string) ($data['captcha_secret_key'] ?? ''));

        $hostData = is_array($data['host'] ?? null) ? $data['host'] : [];

        foreach ($service->hosts() as $key => $host) {
            $want = isset($hostData[$key]);
            $service->setHostEnabled($key, $want);
        }

        $this->app->session()->flash('success', 'Comment settings saved.');
        $this->app->redirect($this->adminBase() . '/settings');
    }

    private function adminBase(): string
    {
        return '/admin' . rtrim((string) $this->app->pluginLoader()->routePrefix('pubvana/comments'), '/');
    }
}
