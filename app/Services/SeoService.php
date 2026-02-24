<?php

namespace App\Services;

class SeoService
{
    public function getMeta(object $entity): array
    {
        $siteName = setting('App.siteName') ?? 'Pubvana';
        $title    = $entity->meta_title ?? $entity->title ?? $siteName;
        $desc     = $entity->meta_description ?? setting('Seo.metaDescription') ?? '';

        return [
            'title'       => esc($title . ' - ' . $siteName),
            'description' => esc($desc),
            'og_title'    => esc($title),
            'og_description' => esc($desc),
            'og_image'    => isset($entity->featured_image) ? base_url($entity->featured_image) : '',
        ];
    }

    public function getDefaultMeta(): array
    {
        $siteName = setting('App.siteName') ?? 'Pubvana';
        $desc     = setting('Seo.metaDescription') ?? '';

        return [
            'title'          => esc($siteName),
            'description'    => esc($desc),
            'og_title'       => esc($siteName),
            'og_description' => esc($desc),
            'og_image'       => '',
        ];
    }

    public function getJsonLd(object $post, ?object $authorProfile = null): string
    {
        $siteName = setting('App.siteName') ?? 'Pubvana';
        $baseUrl  = rtrim(base_url(), '/');

        $data = [
            '@context'      => 'https://schema.org',
            '@type'         => 'Article',
            'headline'      => $post->title ?? '',
            'datePublished' => $post->published_at
                ? (new \DateTime($post->published_at))->format(\DateTime::ATOM)
                : '',
            'dateModified'  => $post->updated_at
                ? (new \DateTime($post->updated_at))->format(\DateTime::ATOM)
                : '',
            'publisher'     => [
                '@type' => 'Organization',
                'name'  => $siteName,
            ],
        ];

        if (! empty($post->featured_image)) {
            $data['image'] = strpos($post->featured_image, '://') !== false
                ? $post->featured_image
                : $baseUrl . '/' . ltrim($post->featured_image, '/');
        }

        $authorName = $authorProfile->display_name ?? ($authorProfile->username ?? 'Author');
        $data['author'] = ['@type' => 'Person', 'name' => $authorName];

        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function getBreadcrumbJsonLd(array $items): string
    {
        $list = [];
        foreach ($items as $position => $item) {
            $list[] = [
                '@type'    => 'ListItem',
                'position' => $position + 1,
                'name'     => $item['name'],
                'item'     => $item['url'],
            ];
        }

        $data = [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $list,
        ];

        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
