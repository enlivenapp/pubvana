<?php

namespace App\Controllers;

use App\Models\PostModel;

class NewsSitemap extends BaseController
{
    public function index(): \CodeIgniter\HTTP\Response
    {
        if (! setting('Seo.newsSitemapEnabled')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $postModel = new PostModel();

        // Google News only indexes articles published in the last 2 days
        $cutoff = date('Y-m-d H:i:s', strtotime('-2 days'));
        $posts  = $postModel->published()
            ->where('published_at >=', $cutoff)
            ->orderBy('published_at', 'DESC')
            ->findAll();

        $siteName = esc(setting('App.siteName') ?? 'Pubvana');

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
        $xml .= '        xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">' . "\n";

        foreach ($posts as $post) {
            $pubDate = (new \DateTime($post->published_at))->format(\DateTime::ATOM);
            $xml .= "  <url>\n";
            $xml .= '    <loc>' . esc(post_url($post->slug)) . "</loc>\n";
            $xml .= "    <news:news>\n";
            $xml .= "      <news:publication>\n";
            $xml .= "        <news:name>{$siteName}</news:name>\n";
            $xml .= "        <news:language>en</news:language>\n";
            $xml .= "      </news:publication>\n";
            $xml .= "      <news:publication_date>{$pubDate}</news:publication_date>\n";
            $xml .= '      <news:title>' . esc($post->title) . "</news:title>\n";
            $xml .= "    </news:news>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return $this->response
            ->setContentType('text/xml')
            ->setBody($xml);
    }
}
