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
}
