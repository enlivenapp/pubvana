<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($seo['title'] ?? site_name()) ?></title>
    <?php if (!empty($seo['description'])): ?>
    <meta name="description" content="<?= esc($seo['description']) ?>">
    <?php endif; ?>
    <?php if (!empty($seo['og_title'])): ?>
    <meta property="og:title" content="<?= esc($seo['og_title']) ?>">
    <meta property="og:description" content="<?= esc($seo['og_description'] ?? '') ?>">
    <?php if (!empty($seo['og_image'])): ?>
    <meta property="og:image" content="<?= esc($seo['og_image']) ?>">
    <?php endif; ?>
    <?php endif; ?>
    <link rel="alternate" type="application/rss+xml" title="<?= esc(site_name()) ?> RSS Feed" href="<?= base_url('feed') ?>">

    <!-- Google Fonts: Inter + Lora -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Lora:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <!-- Ember Theme CSS -->
    <link href="<?= theme_url('css/theme.css') ?>" rel="stylesheet">

    <?php
    // Accent colour (user-configurable, falls back to amber)
    $accentColor = '#f59e0b';
    $t = active_theme();
    if ($t) {
        $row = db_connect()->table('theme_options')
            ->where('theme_id', $t->id)->where('option_key', 'accent_color')
            ->get()->getRowObject();
        if ($row && $row->option_value) { $accentColor = $row->option_value; }
    }
    ?>
    <style>
        :root {
            --ember-accent: <?= esc($accentColor) ?>;
            --ember-accent-dark: color-mix(in srgb, <?= esc($accentColor) ?> 80%, #000);
        }
    </style>

    <?php if ($ga = setting('Seo.googleAnalytics')): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= esc($ga) ?>"></script>
    <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?= esc($ga, 'js') ?>');</script>
    <?php endif; ?>
</head>
<body>

<!-- Navigation -->
<header class="ember-header sticky-top">
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="<?= base_url() ?>">
                <span class="brand-text"><?= esc(site_name()) ?></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarEmber" aria-label="Toggle navigation">
                <i class="fas fa-bars"></i>
            </button>
            <div class="collapse navbar-collapse" id="navbarEmber">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="<?= base_url() ?>">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('blog') ?>">Blog</a></li>
                    <?php foreach (($primary_nav ?? []) as $navItem): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= esc($navItem->url) ?>" target="<?= esc($navItem->target) ?>">
                            <?= esc($navItem->label) ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <form class="d-flex ember-search" action="<?= base_url('search') ?>" method="GET">
                    <div class="input-group input-group-sm">
                        <input class="form-control" type="search" name="q" placeholder="Search…" aria-label="Search">
                        <button class="btn btn-search" type="submit"><i class="fas fa-search"></i></button>
                    </div>
                </form>
            </div>
        </div>
    </nav>
</header>

<!-- Main Content -->
<main class="ember-main py-5">
    <div class="container">
        <?= $main_content ?? '' ?>
    </div>
</main>

<!-- Footer -->
<footer class="ember-footer">
    <div class="container">
        <div class="row py-5">
            <div class="col-lg-4 mb-4 mb-lg-0">
                <div class="footer-brand mb-2"><?= esc(site_name()) ?></div>
                <p class="footer-tagline"><?= esc(site_tagline()) ?></p>
                <div class="social-links mt-3">
                    <?php foreach (($social_links ?? []) as $s): ?>
                        <a href="<?= esc($s->url) ?>" target="_blank" rel="noopener" class="social-link" title="<?= esc($s->platform ?? '') ?>">
                            <i class="<?= esc($s->icon) ?>"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 mb-4 mb-lg-0">
                <?= widget_area('footer-1') ?>
            </div>
            <div class="col-lg-3 col-md-4 mb-4 mb-lg-0">
                <?= widget_area('footer-2') ?>
            </div>
            <div class="col-lg-3 col-md-4">
                <?= widget_area('footer-3') ?>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="row align-items-center">
                <div class="col">
                    <?php
                    $copyright = '';
                    $t = active_theme();
                    if ($t) {
                        $row = db_connect()->table('theme_options')
                            ->where('theme_id', $t->id)->where('option_key', 'footer_copyright')
                            ->get()->getRowObject();
                        $copyright = $row->option_value ?? '';
                    }
                    echo $copyright ? esc($copyright) : '&copy; ' . date('Y') . ' ' . esc(site_name()) . '. All rights reserved.';
                    ?>
                </div>
                <div class="col-auto footer-links">
                    <a href="<?= base_url('feed') ?>"><i class="fas fa-rss me-1"></i>RSS</a>
                    <?php if (setting('Seo.sitemapEnabled')): ?>
                    <a href="<?= base_url('sitemap.xml') ?>">Sitemap</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
