<?php

namespace App\Controllers;

use App\Models\PostModel;

class Feed extends BaseController
{
    public function index(): \CodeIgniter\HTTP\Response
    {
        $postModel = new PostModel();
        $posts     = $postModel->published()
            ->select('title, slug, excerpt, content, content_type, published_at, author_id')
            ->orderBy('published_at', 'DESC')
            ->limit(20)
            ->findAll();

        $siteName = site_name();
        $baseUrl  = base_url();

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
        $xml .= "<channel>\n";
        $xml .= "  <title>" . esc($siteName) . "</title>\n";
        $xml .= "  <link>" . esc($baseUrl) . "</link>\n";
        $xml .= "  <description>" . esc(setting('Seo.metaDescription') ?? '') . "</description>\n";
        $xml .= "  <language>en-us</language>\n";
        $xml .= "  <atom:link href=\"" . esc(base_url('feed')) . "\" rel=\"self\" type=\"application/rss+xml\" />\n";

        foreach ($posts as $post) {
            $content = render_content($post);
            $xml .= "  <item>\n";
            $xml .= "    <title>" . esc($post->title) . "</title>\n";
            $xml .= "    <link>" . esc(post_url($post->slug)) . "</link>\n";
            $xml .= "    <guid isPermaLink=\"true\">" . esc(post_url($post->slug)) . "</guid>\n";
            $xml .= "    <pubDate>" . date('r', strtotime($post->published_at)) . "</pubDate>\n";
            $xml .= "    <description><![CDATA[" . ($post->excerpt ?? excerpt($content)) . "]]></description>\n";
            $xml .= "  </item>\n";
        }

        $xml .= "</channel>\n</rss>";

        return $this->response
            ->setContentType('application/rss+xml')
            ->setBody($xml);
    }
}
