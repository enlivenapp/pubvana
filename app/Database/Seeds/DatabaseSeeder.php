<?php
namespace App\Database\Seeds;
use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call('SettingsSeeder');
        $this->call('ThemeSeeder');
        $this->call('WidgetSeeder');
        $this->call('UserSeeder');
    }
}
