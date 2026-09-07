<?php

declare(strict_types=1);

namespace Pubvana\Controllers\Admin;

use flight\Engine;

/**
 * LoginSecController - Shield sign-in features (Settings > Login).
 *
 * Thin MVC controller: index() renders the toggle form from the declared
 * admin.settings fields (slot 'login_sec'), save() stores each toggle as a
 * boolean in the settings store. Stored rows are folded onto the
 * flight-shield config by PluginLoader at boot, so changes take effect on
 * the next request.
 *
 * @package Pubvana\Controllers\Admin
 */
class LoginSecController extends AdminController
{
    /** @var string One-shot flash key for save results */
    protected const FLASH_KEY = 'login_sec_flash';

    /**
     * @param Engine<object> $app
     */
    public function __construct(Engine $app)
    {
        parent::__construct($app, 'pubvana');
    }

    /**
     * Login settings page: Shield sign-in and registration toggles.
     */
    public function index(): void
    {
        $settings = $this->app->settings();
        $fields = [];

        foreach ($this->app->adext()->get('admin.settings', 'login_sec') as $contributor => $tab) {
            foreach (($tab['fields'] ?? []) as $field) {
                $field['value'] = (bool) $settings->get($field['key'], $field['default'] ?? false);
                $fields[] = $field;
            }
        }

        $this->render('admin/login_sec', [
            'pageTitle' => 'Login',
            'fields'    => $fields,
            'flash'     => $this->app->session()->pullFlash(self::FLASH_KEY),
        ]);
    }

    /**
     * Save login settings to the settings store.
     *
     * Every declared Shield.* key is written: unchecked checkboxes do not
     * appear in POST data, so absence stores false. Takes effect on the
     * next request, when PluginLoader folds the rows onto Shield's config.
     */
    public function save(): void
    {
        $post = (array) ($this->app->request()->data->getData()['settings'] ?? []);
        $settings = $this->app->settings();
        $saved = 0;

        foreach ($this->app->adext()->get('admin.settings', 'login_sec') as $contributor => $tab) {
            foreach (($tab['fields'] ?? []) as $field) {
                $key = $field['key'];
                if (!is_string($key) || $key === '') {
                    continue;
                }

                $value = array_key_exists($key, $post) && (string) $post[$key] === '1';
                $settings->set($key, $value);
                $saved++;
            }
        }

        $this->app->session()->flash(
            self::FLASH_KEY,
            'Login settings saved (' . $saved . ' setting' . ($saved === 1 ? '' : 's') . '). '
            . 'Changes apply the next time anyone visits the site.'
        );
        $this->app->redirect('/admin/login-sec');
    }
}
