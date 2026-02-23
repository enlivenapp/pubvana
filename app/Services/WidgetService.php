<?php

namespace App\Services;

use App\Libraries\BaseWidget;

class WidgetService
{
    public function discover(): array
    {
        $widgets = [];
        foreach (glob(WIDGETS_PATH . '*', GLOB_ONLYDIR) as $dir) {
            $infoFile = $dir . '/widget_info.php';
            if (is_file($infoFile)) {
                $info = require $infoFile;
                $info['folder'] = basename($dir);
                $widgets[]      = $info;
            }
        }
        return $widgets;
    }

    public function getInstance(string $folder): ?BaseWidget
    {
        $classFile = WIDGETS_PATH . $folder . '/' . $this->folderToClass($folder) . '.php';
        if (! is_file($classFile)) {
            return null;
        }
        require_once $classFile;
        $class = $this->folderToClass($folder);
        if (! class_exists($class)) {
            return null;
        }
        return new $class();
    }

    public function renderArea(string $slug): string
    {
        $db = db_connect();
        $instances = $db->table('widget_instances wi')
            ->select('wi.*, w.folder, wa.slug AS area_slug')
            ->join('widget_areas wa', 'wa.id = wi.widget_area_id')
            ->join('widgets w', 'w.id = wi.widget_id')
            ->where('wa.slug', $slug)
            ->where('w.is_active', 1)
            ->orderBy('wi.sort_order', 'ASC')
            ->get()->getResultObject();

        $html = '';
        foreach ($instances as $instance) {
            $widget = $this->getInstance($instance->folder);
            if ($widget) {
                $options = $instance->options_json ? json_decode($instance->options_json, true) : [];
                $html .= $widget->render($options);
            }
        }
        return $html;
    }

    protected function folderToClass(string $folder): string
    {
        return str_replace('_', '', ucwords($folder, '_')) . 'Widget';
    }
}
