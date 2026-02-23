<?php

namespace App\Libraries;

abstract class BaseWidget
{
    protected string $folder = '';

    abstract public function getInfo(): array;

    public function render(array $options = []): string
    {
        $defaults = $this->getDefaults();
        $merged   = array_merge($defaults, $options);
        return $this->buildOutput($merged);
    }

    protected function buildOutput(array $options): string
    {
        return $this->view('widget', $options);
    }

    protected function getDefaults(): array
    {
        $info = $this->getInfo();
        $defaults = [];
        foreach ($info['options'] ?? [] as $key => $cfg) {
            $defaults[$key] = $cfg['default'] ?? '';
        }
        return $defaults;
    }

    protected function view(string $name, array $data = []): string
    {
        $folder = $this->getFolder();
        $path   = WIDGETS_PATH . $folder . '/views/' . $name . '.php';
        if (! is_file($path)) {
            return '';
        }
        return view($path, $data);
    }

    protected function getFolder(): string
    {
        if ($this->folder !== '') {
            return $this->folder;
        }
        // Auto-detect from class name: RecentPostsWidget -> recent_posts
        $class  = get_class($this);
        $short  = basename(str_replace('\\', '/', $class));
        $noSuffix = preg_replace('/Widget$/', '', $short);
        return strtolower(preg_replace('/([A-Z])/', '_$1', lcfirst($noSuffix)));
    }

    public function renderAdminForm(array $options = []): string
    {
        $info     = $this->getInfo();
        $defaults = $this->getDefaults();
        $merged   = array_merge($defaults, $options);
        $folder   = $this->getFolder();
        $path     = WIDGETS_PATH . $folder . '/views/admin_form.php';
        if (! is_file($path)) {
            return '';
        }
        return view($path, ['options' => $merged, 'info' => $info]);
    }
}
