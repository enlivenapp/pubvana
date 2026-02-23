<?php
namespace App\Database\Seeds;
use CodeIgniter\Database\Seeder;

class ThemeSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('themes')->ignore(true)->insert([
            'name'         => 'Default',
            'folder'       => 'default',
            'is_active'    => 1,
            'version'      => '1.0.0',
            'installed_at' => $now,
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);
        $themeId = $this->db->insertID();

        // Flatly and Cyborg ship installed but not active
        foreach (['flatly' => 'Flatly', 'cyborg' => 'Cyborg'] as $folder => $name) {
            $this->db->table('themes')->ignore(true)->insert([
                'name'         => $name,
                'folder'       => $folder,
                'is_active'    => 0,
                'version'      => '1.0.0',
                'installed_at' => $now,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }

        $areas = [
            ['slug' => 'sidebar',       'name' => 'Main Sidebar'],
            ['slug' => 'footer-1',      'name' => 'Footer Column 1'],
            ['slug' => 'footer-2',      'name' => 'Footer Column 2'],
            ['slug' => 'footer-3',      'name' => 'Footer Column 3'],
            ['slug' => 'before-content','name' => 'Before Content'],
        ];
        foreach ($areas as $area) {
            $this->db->table('widget_areas')->insert([
                'name'     => $area['name'],
                'slug'     => $area['slug'],
                'theme_id' => $themeId,
            ]);
        }
    }
}
