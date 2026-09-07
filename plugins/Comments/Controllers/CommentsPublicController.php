<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Comments\Controllers;

use Pubvana\Controllers\Public\PublicController;

/**
 * CommentsPublicController - Public comment submission and (optional) display.
 *
 * Most hosts render comments inline via the injection contract
 * (CommentService::render()/dataFor()) rather than hitting these routes.
 * The store() action is the form target that host views post to; index()
 * exists as a standalone fallback page.
 *
 * @package Pubvana\Plugins\Comments\Controllers
 */
class CommentsPublicController extends PublicController
{
    public function __construct(\flight\Engine $app)
    {
        parent::__construct($app, 'pubvana.comments');
    }

    /**
     * Standalone comment display for a content item (fallback).
     */
    public function index(string $type, string $id): void
    {
        $service = $this->app->comments();
        $allow = (bool) ($this->app->request()->query->allow ?? true);

        $data = $service->dataFor($type, (int) $id, $allow);
        $this->render('comments', $data);
    }

    /**
     * Handle comment submission from a host view form.
     */
    public function store(string $type, string $id): void
    {
        $referrer = $this->app->request()->referrer ?: '/';
        $service = $this->app->comments();

        if (!$service->isEnabled()) {
            $this->app->redirect($referrer);
            return;
        }

        if (!$service->isTypeEnabled($type)) {
            $this->app->redirect($referrer);
            return;
        }

        $post = $this->app->request()->data;
        $userId = function_exists('user_id') ? user_id() : null;

        if ($userId === null && !$service->allowsGuestComments()) {
            $this->redirectWithError($referrer, 'Comments are only open to registered users.');
            return;
        }

        $body = trim((string) ($post->body ?? ''));

        if ($body === '') {
            $this->redirectWithError($referrer, 'A comment cannot be empty.');
            return;
        }

        $data = [
            'commentable_type' => $type,
            'commentable_id'   => (int) $id,
            'body'             => $body,
            'ip_address'       => (string) $this->app->request()->ip,
        ];

        if (!empty($post->parent_id)) {
            $data['parent_id'] = (int) $post->parent_id;
        }

        $captchaField = $this->app->captcha()->postField();
        if ($captchaField !== '') {
            $data['captcha_token'] = (string) ($post->$captchaField ?? '');
        }

        if ($userId !== null) {
            $data['user_id'] = (int) $userId;
        } else {
            $guestName = trim((string) ($post->guest_name ?? ''));
            if ($guestName === '') {
                $this->redirectWithError($referrer, 'Please provide your name.');
                return;
            }
            $data['guest_name']  = $guestName;
            $data['guest_email'] = trim((string) ($post->guest_email ?? ''));
            $data['guest_website'] = trim((string) ($post->guest_website ?? ''));
        }

        try {
            $service->create($data);
        } catch (\InvalidArgumentException $e) {
            $this->redirectWithError($referrer, $e->getMessage());
            return;
        }

        $this->app->redirect($referrer);
    }

    /**
     * Redirect back to the referrer with a comment_error query flag.
     */
    private function redirectWithError(string $referrer, string $message): void
    {
        $separator = str_contains($referrer, '?') ? '&' : '?';
        $this->app->redirect($referrer . $separator . 'comment_error=' . urlencode($message));
    }
}
