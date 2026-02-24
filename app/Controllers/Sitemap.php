<?php

namespace App\Controllers;

use App\Models\PageModel;
use App\Models\PostModel;

class Sitemap extends BaseController
{
    public function index(): \CodeIgniter\HTTP\Response
    {
        $postModel = new PostModel();
        $pageModel = new PageModel();

        $posts = $postModel->published()->select('slug, updated_at, published_at')->orderBy('published_at', 'DESC')->findAll();
        $pages = $pageModel->published()->select('slug, updated_at')->findAll();

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Home
        $xml .= $this->urlTag(base_url('/'), date('Y-m-d'), 'daily', '1.0');

        foreach ($pages as $page) {
            $xml .= $this->urlTag(
                base_url($page->slug),
                substr($page->updated_at, 0, 10),
                'monthly',
                '0.8'
            );
        }

        foreach ($posts as $post) {
            $xml .= $this->urlTag(
                post_url($post->slug),
                substr($post->updated_at ?? $post->published_at, 0, 10),
                'weekly',
                '0.9'
            );
        }

        $xml .= '</urlset>';

        return $this->response
            ->setContentType('text/xml')
            ->setBody($xml);
    }

    public function robots(): \CodeIgniter\HTTP\Response
    {
        $body  = "User-agent: *\n";
        $body .= "Disallow: /admin/\n";
        $body .= "Disallow: /login\n";
        $body .= "Disallow: /register\n";
        if (setting('Seo.sitemapEnabled')) {
            $body .= "Sitemap: " . base_url('sitemap.xml') . "\n";
        }
        if (setting('Seo.newsSitemapEnabled')) {
            $body .= "Sitemap: " . base_url('news-sitemap.xml') . "\n";
        }

        return $this->response
            ->setContentType('text/plain')
            ->setBody($body);
    }

    private function urlTag(string $loc, string $lastmod, string $changefreq, string $priority): string
    {
        return "  <url>\n" .
               "    <loc>" . esc($loc) . "</loc>\n" .
               "    <lastmod>{$lastmod}</lastmod>\n" .
               "    <changefreq>{$changefreq}</changefreq>\n" .
               "    <priority>{$priority}</priority>\n" .
               "  </url>\n";
    }
}
