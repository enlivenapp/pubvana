<?php
namespace App\Database\Seeds;
use CodeIgniter\Database\Seeder;

class WidgetSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');
        $widgets = [
            ['name' => 'Recent Posts',     'folder' => 'recent_posts',     'description' => 'Display recent posts',     'version' => '1.0.0'],
            ['name' => 'Categories List',  'folder' => 'categories_list',  'description' => 'Display categories',       'version' => '1.0.0'],
            ['name' => 'Tag Cloud',        'folder' => 'tag_cloud',        'description' => 'Display a tag cloud',      'version' => '1.0.0'],
            ['name' => 'Social Links',     'folder' => 'social_links',     'description' => 'Display social links',     'version' => '1.0.0'],
            ['name' => 'Text Block',       'folder' => 'text_block',       'description' => 'Display custom HTML text', 'version' => '1.0.0'],
            ['name' => 'Search Form',      'folder' => 'search_form',      'description' => 'Display a search form',   'version' => '1.0.0'],
            ['name' => 'Recent Comments',  'folder' => 'recent_comments',  'description' => 'Display recent comments', 'version' => '1.0.0'],
            ['name' => 'Archive List',     'folder' => 'archive_list',     'description' => 'Display post archives',   'version' => '1.0.0'],
        ];
        foreach ($widgets as $w) {
            $w['is_active']  = 1;
            $w['created_at'] = $now;
            $w['updated_at'] = $now;
            $this->db->table('widgets')->ignore(true)->insert($w);
        }
    }
}
