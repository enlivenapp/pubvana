<?php
namespace App\Database\Seeds;
use CodeIgniter\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run()
    {
        $settings = [
            ['class' => 'App',         'key' => 'siteName',          'value' => 'Pubvana CMS',   'type' => 'string'],
            ['class' => 'App',         'key' => 'siteTagline',       'value' => 'A modern CMS',  'type' => 'string'],
            ['class' => 'App',         'key' => 'siteEmail',         'value' => 'admin@example.com', 'type' => 'string'],
            ['class' => 'App',         'key' => 'postsPerPage',      'value' => '10',             'type' => 'integer'],
            ['class' => 'App',         'key' => 'commentsEnabled',   'value' => '1',              'type' => 'boolean'],
            ['class' => 'App',         'key' => 'commentModeration', 'value' => '1',              'type' => 'boolean'],
            ['class' => 'App',         'key' => 'frontPageType',     'value' => 'blog',           'type' => 'string'],
            ['class' => 'App',         'key' => 'frontPageId',       'value' => null,             'type' => 'NULL'],
            ['class' => 'Seo',         'key' => 'metaDescription',   'value' => '',               'type' => 'string'],
            ['class' => 'Seo',         'key' => 'googleAnalytics',   'value' => '',               'type' => 'string'],
            ['class' => 'Seo',         'key' => 'sitemapEnabled',    'value' => '1',              'type' => 'boolean'],
            ['class' => 'Email',       'key' => 'fromName',          'value' => 'Pubvana CMS',   'type' => 'string'],
            ['class' => 'Email',       'key' => 'fromEmail',         'value' => 'no-reply@example.com', 'type' => 'string'],
            ['class' => 'Marketplace', 'key' => 'apiUrl',            'value' => 'https://pubvana.org/api/marketplace', 'type' => 'string'],
            ['class' => 'Marketplace', 'key' => 'storeUrl',          'value' => 'https://pubvana.org/store', 'type' => 'string'],
        ];
        $now = date('Y-m-d H:i:s');
        foreach ($settings as $s) {
            $s['created_at'] = $now;
            $s['updated_at'] = $now;
            $this->db->table('settings')->ignore(true)->insert($s);
        }
    }
}
