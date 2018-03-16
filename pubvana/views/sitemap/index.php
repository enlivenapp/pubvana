<?php header('Content-type: text/xml'); ?>
<?= '<?xml version="1.0" encoding="UTF-8" ?>' ?>

<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc><?= base_url();?></loc> 
        <priority>1.0</priority>
        <changefreq>daily</changefreq>
    </url>
    <url>
        <loc><?= site_url('blog');?></loc> 
        <priority>1.0</priority>
        <changefreq>daily</changefreq>
    </url>


    <?php if ($data): ?>

        <?php foreach($data as $url): ?>
            <url>
                <loc><?= $url->url ?></loc>
                <priority>0.5</priority>
                <changefreq>monthly</changefreq>
                <?php if ($url->date_modified): ?>
                <lastmod><?= $url->date_modified ?></lastmod>
                <?php else: ?>
                <lastmod><?= $url->date ?></lastmod>
                <?php endif ?>
            </url>
        <?php endforeach ?>
    <?php endif ?>

</urlset>
