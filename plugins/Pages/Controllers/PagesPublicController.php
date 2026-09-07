<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Pages\Controllers;

use Pubvana\Controllers\Public\PublicController;
use flight\Engine;

/**
 * PagesPublicController - Public page rendering.
 *
 * Handles rendering published pages by slug. Admin CRUD is handled
 * by PagesAdminController.
 *
 * @package Pubvana\Plugins\Pages\Controllers
 */
class PagesPublicController extends PublicController
{
    public function __construct(Engine $app)
    {
        parent::__construct($app, 'pubvana.pages');
    }

    /**
     * Public page listing.
     *
     * Redirects to the homepage — pages are accessed by slug, not listed.
     */
    public function index(): void
    {
        $this->app->redirect('/');
    }

    /**
     * Public page rendering.
     *
     * Finds a published page by slug and renders it through the
     * theme layout with full global data.
     *
     * @param string $slug        Page slug
     * @param bool   $isHomepage  True when this page is serving the homepage
     */
    public function view(string $slug, bool $isHomepage = false): void
    {
        $page = $this->app->pages()->findPageBySlug($slug);

        if ($page === null) {
            $this->app->halt(404, 'Page not found');
            return;
        }

        $this->render('page', [
            'title'          => $page->title,
            'content'        => $page->content,
            'featured_image' => null,
            'ai_disclosure'  => $this->aiDisclosure((int) $page->ai_generated),
            'commentable'    => ['type' => 'page', 'id' => (int) $page->id],
            'allow_comments' => (bool) $page->allow_comments,
            'is_homepage'    => $isHomepage,
            'seo_context'    => [
                'content_type' => 'page',
                'content_id'   => (int) $page->id,
                'title'        => $page->title,
                'description'  => '',
                'image'        => '',
                'ai_generated' => !empty($page->ai_generated),
                'updated_at'   => $page->updated_at,
                'og_type'      => 'website',
            ],
        ]);
    }

    /**
     * Whether to show the visible AI-assistance disclosure for this page.
     */
    private function aiDisclosure(int|bool $aiGenerated): bool
    {
        if (empty($aiGenerated)) {
            return false;
        }

        return (bool) $this->app->settings()->get('Seo.ai_disclosure_enabled', true);
    }
}
