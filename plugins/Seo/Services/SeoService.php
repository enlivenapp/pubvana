<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Seo\Services;

use Pubvana\Plugins\Seo\Models\SeoMeta;
use flight\Engine;

/**
 * Core SEO service — meta tag assembly, head output, canonical URLs.
 *
 * Self-discovers content from the current request URL.
 * Other plugins can inject additional tags via addMeta() and addTag().
 */
class SeoService
{
    protected \PDO $pdo;
    /** @var Engine<object> */
    protected Engine $app;

    /** @var array<string, mixed> Current page SEO context, auto-detected from request. */
    protected array $context = [];

    /** @var array<int, array{name: string, content: string}> Extra meta tags injected by other plugins: [{name, content}] */
    protected array $extraMeta = [];

    /** @var array<int, string> Extra raw tags injected by other plugins (arbitrary HTML strings) */
    protected array $extraTags = [];

    /**
     * @param Engine<object> $app
     */
    public function __construct(\PDO $pdo, Engine $app)
    {
        $this->pdo = $pdo;
        $this->app = $app;
    }

    // -----------------------------------------------------------------
    // Content detection — called before rendering to set current context
    // -----------------------------------------------------------------

    /**
     * Detect the current content from the request URL and set context.
     */
    public function detectContent(): void
    {
        $uri = $this->app->request()->url ?? ($_SERVER['REQUEST_URI'] ?? '/');
        $uri = strtok($uri, '?');
        $uri = $uri === false ? '' : trim($uri, '/');

        if ($uri === '') {
            $this->context = ['content_type' => 'home'];
            return;
        }

        // Blog post: blog/{slug}
        if (preg_match('#^blog/([a-z0-9\-]+)$#', $uri, $m)) {
            try {
                $post = $this->app->blog()->findPostBySlug($m[1]);
                if ($post && $post->status === 'published') {
                    $author = $this->resolveAuthor((int) $post->author_id);

                    $this->context = [
                        'content_type' => 'post',
                        'content_id'   => (int) $post->id,
                        'title'        => $post->title,
                        'description'  => $post->excerpt ?? '',
                        'url'          => $this->getCurrentUrl(),
                        'image'        => $this->resolveImageUrl($post->featured_image ?? ''),
                        'author'       => $author,
                        'ai_generated' => !empty($post->ai_generated),
                        'published_at' => $post->published_at,
                        'updated_at'   => $post->updated_at,
                        'og_type'      => 'article',
                    ];
                    return;
                }
            } catch (\Throwable) {
            }
        }

        // Blog category: blog/category/{slug}
        if (preg_match('#^blog/category/([a-z0-9\-]+)$#', $uri, $m)) {
            $this->context = [
                'content_type' => 'archive',
                'url'          => $this->getCurrentUrl(),
                'og_type'      => 'website',
            ];
            return;
        }

        // Blog tag: blog/tag/{slug}
        if (preg_match('#^blog/tag/([a-z0-9\-]+)$#', $uri, $m)) {
            $this->context = [
                'content_type' => 'archive',
                'url'          => $this->getCurrentUrl(),
                'og_type'      => 'website',
            ];
            return;
        }

        // Blog listing
        if ($uri === 'blog') {
            $this->context = [
                'content_type' => 'archive',
                'url'          => $this->getCurrentUrl(),
                'og_type'      => 'website',
            ];
            return;
        }

        // Page: page/{slug}
        if (preg_match('#^page/([a-z0-9\-]+)$#', $uri, $m)) {
            try {
                $page = $this->app->pages()->findPageBySlug($m[1]);
                if ($page && $page->status === 'published') {
                    $this->context = [
                        'content_type' => 'page',
                        'content_id'   => (int) $page->id,
                        'title'        => $page->title,
                        'description'  => '',
                        'url'          => $this->getCurrentUrl(),
                        'image'        => '',
                        'ai_generated' => !empty($page->ai_generated),
                        'updated_at'   => $page->updated_at,
                        'og_type'      => 'website',
                    ];
                    return;
                }
            } catch (\Throwable) {
            }
        }

        // Unknown route — minimal context
        $this->context = ['content_type' => 'unknown', 'url' => $this->getCurrentUrl()];
    }

    // -----------------------------------------------------------------
    // External injection — other plugins add tags here
    // -----------------------------------------------------------------

    /**
     * Add a <meta name="..." content="..."> tag.
     */
    public function addMeta(string $name, string $content): void
    {
        $this->extraMeta[] = ['name' => $name, 'content' => $content];
    }

    /**
     * Add an arbitrary raw HTML tag to the head output.
     */
    public function addTag(string $html): void
    {
        $this->extraTags[] = $html;
    }

    // -----------------------------------------------------------------
    // Context access
    // -----------------------------------------------------------------

    /**
     * @param array<string, mixed> $context
     */
    public function setContext(array $context): void
    {
        $this->context = $context;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    // -----------------------------------------------------------------
    // Meta record access
    // -----------------------------------------------------------------

    /**
     * Get SEO meta for a content item, or null if none exists.
     */
    public function getMeta(string $contentType, int $contentId): ?SeoMeta
    {
        $model = new SeoMeta($this->pdo);
        return $model->findByContent($contentType, $contentId);
    }

    /**
     * Save or update SEO meta for a content item.
     */
    /**
     * @param array<string, mixed> $data
     */
    public function saveMeta(string $contentType, int $contentId, array $data): SeoMeta
    {
        $model = new SeoMeta($this->pdo);
        $existing = $model->findByContent($contentType, $contentId);

        $now = date('Y-m-d H:i:s');

        if ($existing !== null) {
            $record = $existing;
            $record->updated_at = $now;
        } else {
            $record = new SeoMeta($this->pdo);
            $record->content_type = $contentType;
            $record->content_id = $contentId;
            $record->created_at = $now;
            $record->updated_at = $now;
        }

        $allowed = [
            'meta_title', 'meta_description', 'canonical_url', 'robots_directive',
            'og_title', 'og_description', 'og_image', 'og_type',
            'twitter_card', 'schema_type', 'seo_score', 'hreflang',
        ];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $record->$field = $data[$field] !== '' ? $data[$field] : null;
            }
        }

        // Handle focus_keywords array
        if (isset($data['focus_keywords'])) {
            if (is_array($data['focus_keywords'])) {
                $record->setFocusKeywordsArray($data['focus_keywords']);
            } else {
                $keywords = array_filter(array_map('trim', explode(',', $data['focus_keywords'])));
                $record->setFocusKeywordsArray($keywords);
            }
        }

        if ($existing !== null) {
            $record->save();
        } else {
            $record->insert();
        }

        return $record;
    }

    // -----------------------------------------------------------------
    // Title assembly
    // -----------------------------------------------------------------

    /**
     * Build the final <title> tag value.
     */
    public function buildTitle(): string
    {
        $settings = $this->app->settings();
        $separator = $settings->get('Seo.title_separator', '|');
        $siteName = $settings->get('CMS.siteName') ?? '';

        // Check for per-content meta_title override
        $metaTitle = $this->getMetaField('meta_title');
        if (!empty($metaTitle)) {
            return $metaTitle;
        }

        // Build from template
        $title = $this->context['title'] ?? $siteName;
        $template = $settings->get('Seo.title_template', '{title} {sep} {site_name}');

        return str_replace(
            ['{title}', '{sep}', '{site_name}'],
            [$title, $separator, $siteName],
            $template
        );
    }

    /**
     * Get the meta description for the current context.
     */
    public function buildDescription(): string
    {
        $metaDesc = $this->getMetaField('meta_description');
        if (!empty($metaDesc)) {
            return $metaDesc;
        }

        // Fallback to context description (excerpt, page content snippet, etc.)
        $desc = $this->context['description'] ?? '';

        // Truncate to 160 chars
        if (mb_strlen($desc) > 160) {
            $desc = mb_substr($desc, 0, 157) . '...';
        }

        return $desc;
    }

    /**
     * Get the canonical URL for the current page.
     */
    public function buildCanonical(): string
    {
        $override = $this->getMetaField('canonical_url');
        if (!empty($override)) {
            return $override;
        }

        return $this->context['url'] ?? $this->getCurrentUrl();
    }

    /**
     * Get robots directive for the current page.
     */
    public function buildRobots(): ?string
    {
        return $this->getMetaField('robots_directive');
    }

    /**
     * Get the hreflang value for the current page.
     */
    public function buildHreflang(): string
    {
        $hreflang = $this->getMetaField('hreflang');
        if (!empty($hreflang)) {
            return $hreflang;
        }

        $settings = $this->app->settings();
        return $settings->get('Seo.default_language', 'en') ?: 'en';
    }

    // -----------------------------------------------------------------
    // Open Graph
    // -----------------------------------------------------------------

    /**
     * Build Open Graph meta tags array.
     *
     * @return array<string, string>
     */
    public function buildOpenGraph(): array
    {
        $settings = $this->app->settings();

        $ogTitle = $this->getMetaField('og_title') ?: $this->buildTitle();
        $ogDesc = $this->getMetaField('og_description') ?: $this->buildDescription();
        $ogImage = $this->resolveImageUrl(
            $this->getMetaField('og_image')
            ?: ($this->context['image'] ?? $settings->get('Seo.default_og_image') ?? '')
        );
        $ogType = $this->getMetaField('og_type')
            ?: ($this->context['og_type'] ?? 'website');
        $ogUrl = $this->buildCanonical();

        $tags = [
            'og:title'       => $ogTitle,
            'og:description' => $ogDesc,
            'og:url'         => $ogUrl,
            'og:type'        => $ogType,
            'og:site_name'   => $settings->get('CMS.siteName') ?? '',
            'og:locale'      => str_replace('-', '_', $this->buildHreflang()),
        ];

        if (!empty($ogImage)) {
            $tags['og:image'] = $ogImage;
        }

        return $tags;
    }

    /**
     * Build Twitter Card meta tags array.
     *
     * @return array<string, string>
     */
    public function buildTwitterCard(): array
    {
        $cardType = $this->getMetaField('twitter_card') ?: 'summary_large_image';

        $tags = [
            'twitter:card'        => $cardType,
            'twitter:title'       => $this->getMetaField('og_title') ?: $this->buildTitle(),
            'twitter:description' => $this->getMetaField('og_description') ?: $this->buildDescription(),
        ];

        $settings = $this->app->settings();
        $image = $this->resolveImageUrl(
            $this->getMetaField('og_image')
            ?: ($this->context['image'] ?? $settings->get('Seo.default_og_image') ?? '')
        );

        if (!empty($image)) {
            $tags['twitter:image'] = $image;
        }

        return $tags;
    }

    // -----------------------------------------------------------------
    // Full head output
    // -----------------------------------------------------------------

    /**
     * Render all SEO tags as HTML for injection into the page <head>.
     * Does NOT include <title> — that stays in {{ header.title }}.
     */
    public function renderHead(): string
    {
        $lines = [];

        // Description
        $desc = $this->buildDescription();
        if (!empty($desc)) {
            $lines[] = '<meta name="description" content="' . htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') . '">';
        }

        // Canonical
        $canonical = $this->buildCanonical();
        if (!empty($canonical)) {
            $lines[] = '<link rel="canonical" href="' . htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') . '">';
        }

        // Robots
        $robots = $this->buildRobots();
        if (!empty($robots)) {
            $lines[] = '<meta name="robots" content="' . htmlspecialchars($robots, ENT_QUOTES, 'UTF-8') . '">';
        }

        // Hreflang
        $hreflang = $this->buildHreflang();
        if (!empty($canonical)) {
            $lines[] = '<link rel="alternate" hreflang="' . htmlspecialchars($hreflang, ENT_QUOTES, 'UTF-8')
                . '" href="' . htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') . '">';
        }

        // Open Graph
        foreach ($this->buildOpenGraph() as $property => $content) {
            if (!empty($content)) {
                $lines[] = '<meta property="' . htmlspecialchars($property, ENT_QUOTES, 'UTF-8')
                    . '" content="' . htmlspecialchars($content, ENT_QUOTES, 'UTF-8') . '">';
            }
        }

        // Twitter Card
        foreach ($this->buildTwitterCard() as $name => $content) {
            if (!empty($content)) {
                $lines[] = '<meta name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8')
                    . '" content="' . htmlspecialchars($content, ENT_QUOTES, 'UTF-8') . '">';
            }
        }

        // Verification codes
        $settings = $this->app->settings();
        $googleVerify = $settings->get('Seo.verification_google');
        if (!empty($googleVerify)) {
            $lines[] = '<meta name="google-site-verification" content="' . htmlspecialchars($googleVerify, ENT_QUOTES, 'UTF-8') . '">';
        }
        $bingVerify = $settings->get('Seo.verification_bing');
        if (!empty($bingVerify)) {
            $lines[] = '<meta name="msvalidate.01" content="' . htmlspecialchars($bingVerify, ENT_QUOTES, 'UTF-8') . '">';
        }

        // Extra meta from other plugins
        foreach ($this->extraMeta as $meta) {
            $lines[] = '<meta name="' . htmlspecialchars($meta['name'], ENT_QUOTES, 'UTF-8')
                . '" content="' . htmlspecialchars($meta['content'], ENT_QUOTES, 'UTF-8') . '">';
        }

        // Extra raw tags from other plugins
        foreach ($this->extraTags as $tag) {
            $lines[] = $tag;
        }

        // JSON-LD structured data
        $schema = $this->app->seoSchema();
        $schemaOutput = $schema->render($this->context);
        if (!empty($schemaOutput)) {
            $lines[] = $schemaOutput;
        }

        return implode("\n    ", $lines);
    }

    // -----------------------------------------------------------------
    // Internal helpers
    // -----------------------------------------------------------------

    /**
     * Get a field from the seo_meta record for the current context, if one exists.
     */
    protected function getMetaField(string $field): ?string
    {
        if (empty($this->context['content_type']) || empty($this->context['content_id'])) {
            return null;
        }

        static $metaCache = [];
        $cacheKey = $this->context['content_type'] . ':' . $this->context['content_id'];

        if (!array_key_exists($cacheKey, $metaCache)) {
            $metaCache[$cacheKey] = $this->getMeta(
                $this->context['content_type'],
                (int) $this->context['content_id']
            );
        }

        $meta = $metaCache[$cacheKey];
        if ($meta === null) {
            return null;
        }

        $value = $meta->$field ?? null;
        return !empty($value) ? (string) $value : null;
    }

    /**
     * Get the current request URL (full, with scheme and host).
     */
    protected function getCurrentUrl(): string
    {
        $request = $this->app->request();
        $scheme = $request->secure ? 'https' : 'http';
        $host = $request->host ?? ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $uri = $request->url ?? ($_SERVER['REQUEST_URI'] ?? '/');

        // Strip query string for canonical
        $uri = strtok($uri, '?');

        return $scheme . '://' . $host . $uri;
    }

    /**
     * Resolve a structured author node from a user ID.
     *
     * Never returns an AI/model identity: the author is always the human who
     * owns the account (editorial responsibility). Enriched with profile
     * data (display name, sameAs, job title, works-for) for JSON-LD.
     *
     * @return array{name: string, username: string, url: string, sameAs: string[], jobTitle: ?string, worksFor: ?string}|null
     */
    protected function resolveAuthor(int $userId): ?array
    {
        if ($userId <= 0) {
            return null;
        }

        $profile = null;
        try {
            $profile = $this->app->profiles()->findByUserId($userId);
        } catch (\Throwable) {
        }

        $username = '';
        try {
            $user = (new \Enlivenapp\FlightShield\Models\User($this->pdo))->findById($userId);
            $username = $user->username ?? '';
        } catch (\Throwable) {
        }

        $name = ($profile && !empty($profile->display_name))
            ? (string) $profile->display_name
            : $username;

        if ($name === '') {
            return null;
        }

        $sameAs = [];
        if ($profile) {
            foreach (['website', 'twitter', 'facebook', 'linkedin'] as $field) {
                if (!empty($profile->$field)) {
                    $sameAs[] = (string) $profile->$field;
                }
            }
        }

        return [
            'name'     => $name,
            'username' => $username,
            'url'      => $username !== '' ? $this->getSiteUrl() . '/profile/' . $username : '',
            'sameAs'   => $sameAs,
            'jobTitle' => $profile && !empty($profile->job_title) ? (string) $profile->job_title : null,
            'worksFor' => $profile && !empty($profile->works_for) ? (string) $profile->works_for : null,
        ];
    }

    /**
     * Ensure an image path is a full URL.
     */
    public function resolveImageUrl(?string $path): string
    {
        if (empty($path)) {
            return '';
        }

        // Already a full URL
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        return $this->getSiteUrl() . '/' . ltrim($path, '/');
    }

    /**
     * Absolute site base URL, preferring the configured site URL.
     */
    protected function getSiteUrl(): string
    {
        $siteUrl = $this->app->settings()->get('CMS.siteUrl');
        if (!empty($siteUrl)) {
            return rtrim($siteUrl, '/');
        }

        $request = $this->app->request();
        $scheme = $request->secure ? 'https' : 'http';
        $host = $request->host ?? ($_SERVER['HTTP_HOST'] ?? 'localhost');
        return $scheme . '://' . $host;
    }
}
