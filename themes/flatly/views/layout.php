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

    <!-- Bootswatch Flatly -->
    <link href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.3/dist/flatly/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <!-- Theme CSS -->
    <link href="<?= theme_url('css/theme.css') ?>" rel="stylesheet">

    <?php if ($ga = setting('Seo.googleAnalytics')): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= esc($ga) ?>"></script>
    <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?= esc($ga, 'js') ?>');</script>
    <?php endif; ?>
</head>
<body>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= base_url() ?>">
            <?= esc(site_name()) ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarMain">
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
            <form class="d-flex" action="<?= base_url('search') ?>" method="GET">
                <input class="form-control form-control-sm me-2" type="search" name="q" placeholder="Search…" aria-label="Search">
                <button class="btn btn-outline-light btn-sm" type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>
    </div>
</nav>

<!-- Main Content -->
<main class="py-5">
    <div class="container">
        <?= $main_content ?? '' ?>
    </div>
</main>

<!-- Footer -->
<footer class="bg-primary text-white py-5 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-md-3 mb-4">
                <h5><?= esc(site_name()) ?></h5>
                <p class="text-white-50"><?= esc(site_tagline()) ?></p>
                <?php foreach (($social_links ?? []) as $s): ?>
                    <a href="<?= esc($s->url) ?>" class="text-white-50 me-2" target="_blank" rel="noopener">
                        <i class="<?= esc($s->icon) ?>"></i>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="col-md-3 mb-4"><?= widget_area('footer-1') ?></div>
            <div class="col-md-3 mb-4"><?= widget_area('footer-2') ?></div>
            <div class="col-md-3 mb-4"><?= widget_area('footer-3') ?></div>
        </div>
        <hr class="border-white-50">
        <div class="row">
            <div class="col text-center text-white-50 small">
                <?php
                $copyright = '';
                $t = active_theme();
                if ($t) {
                    $copyright = db_connect()->table('theme_options')
                        ->where('theme_id', $t->id)->where('option_key', 'footer_copyright')
                        ->get()->getRowObject()->option_value ?? '';
                }
                echo $copyright ? esc($copyright) : '&copy; ' . date('Y') . ' ' . esc(site_name()) . '. All rights reserved.';
                ?>
                &nbsp;&middot;&nbsp;
                <a href="<?= base_url('feed') ?>" class="text-white-50"><i class="fas fa-rss"></i> RSS</a>
                <?php if (setting('Seo.sitemapEnabled')): ?>
                &nbsp;&middot;&nbsp;
                <a href="<?= base_url('sitemap.xml') ?>" class="text-white-50">Sitemap</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
