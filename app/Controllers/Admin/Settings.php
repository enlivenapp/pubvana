<?php

namespace App\Controllers\Admin;

use App\Models\PageModel;

class Settings extends BaseAdminController
{
    public function index(): string
    {
        if (! auth()->user()->can('admin.settings')) {
            return redirect()->to('/admin')->with('error', 'Permission denied.');
        }

        $pages = (new PageModel())->where('status', 'published')->findAll();

        return $this->adminView('settings/index', array_merge($this->baseData('Settings', 'settings'), [
            'pages' => $pages,
        ]));
    }

    public function saveGeneral()
    {
        setting()->set('App.siteName',      $this->request->getPost('site_name'));
        setting()->set('App.siteTagline',   $this->request->getPost('site_tagline'));
        setting()->set('App.siteEmail',     $this->request->getPost('site_email'));
        setting()->set('App.postsPerPage',  (int) $this->request->getPost('posts_per_page'));
        setting()->set('App.commentsEnabled', (bool) $this->request->getPost('comments_enabled'));
        setting()->set('App.commentModeration', (bool) $this->request->getPost('comment_moderation'));

        $fpType = $this->request->getPost('front_page_type');
        if (! in_array($fpType, ['blog', 'page'], true)) {
            $fpType = 'blog';
        }
        $fpId = $this->request->getPost('front_page_id');
        setting()->set('App.frontPageType', $fpType);
        setting()->set('App.frontPageId',   ($fpType === 'page' && $fpId) ? (int) $fpId : null);

        return redirect()->to('/admin/settings')->with('success', 'General settings saved.');
    }

    public function saveSeo()
    {
        setting()->set('Seo.metaDescription', $this->request->getPost('meta_description'));
        setting()->set('Seo.googleAnalytics', $this->request->getPost('google_analytics'));
        setting()->set('Seo.sitemapEnabled',  (bool) $this->request->getPost('sitemap_enabled'));
        return redirect()->to('/admin/settings#seo')->with('success', 'SEO settings saved.');
    }

    public function saveEmail()
    {
        setting()->set('Email.fromName',  $this->request->getPost('from_name'));
        setting()->set('Email.fromEmail', $this->request->getPost('from_email'));
        return redirect()->to('/admin/settings#email')->with('success', 'Email settings saved.');
    }

    public function saveSocial()
    {
        if (! auth()->user()->can('admin.settings')) {
            return redirect()->to('/admin')->with('error', 'Permission denied.');
        }

        // OAuth login credentials
        $this->writeEnvKey('oauth.google.clientId',         $this->request->getPost('google_client_id') ?? '');
        $this->writeEnvKey('oauth.google.clientSecret',     $this->request->getPost('google_client_secret') ?? '');
        $this->writeEnvKey('oauth.facebook.clientId',       $this->request->getPost('facebook_client_id') ?? '');
        $this->writeEnvKey('oauth.facebook.clientSecret',   $this->request->getPost('facebook_client_secret') ?? '');

        return redirect()->to('/admin/settings#social')->with('success', 'Social login settings saved.');
    }

    public function saveSocialSharing()
    {
        if (! auth()->user()->can('admin.settings')) {
            return redirect()->to('/admin')->with('error', 'Permission denied.');
        }

        // Twitter / X sharing credentials
        $this->writeEnvKey('oauth.twitter.apiKey',      $this->request->getPost('twitter_api_key') ?? '');
        $this->writeEnvKey('oauth.twitter.apiSecret',   $this->request->getPost('twitter_api_secret') ?? '');
        $this->writeEnvKey('oauth.twitter.accessToken', $this->request->getPost('twitter_access_token') ?? '');
        $this->writeEnvKey('oauth.twitter.accessSecret',$this->request->getPost('twitter_access_secret') ?? '');

        // Facebook sharing page credentials
        $this->writeEnvKey('sharing.facebook.pageId',       $this->request->getPost('fb_page_id') ?? '');
        $this->writeEnvKey('sharing.facebook.pageToken',    $this->request->getPost('fb_page_token') ?? '');

        return redirect()->to('/admin/settings#sharing')->with('success', 'Social sharing settings saved.');
    }

    /**
     * Write or update a key=value line in the .env file.
     * Only permitted keys may be written; values are stripped of newlines.
     * Skips write if value is empty, preserving any existing secret.
     */
    protected function writeEnvKey(string $key, string $value): void
    {
        static $allowedKeys = [
            'oauth.google.clientId',       'oauth.google.clientSecret',
            'oauth.facebook.clientId',     'oauth.facebook.clientSecret',
            'oauth.twitter.apiKey',        'oauth.twitter.apiSecret',
            'oauth.twitter.accessToken',   'oauth.twitter.accessSecret',
            'sharing.facebook.pageId',     'sharing.facebook.pageToken',
        ];
        if (! in_array($key, $allowedKeys, true)) {
            return;
        }

        // Strip newlines — prevents an attacker from injecting extra .env lines
        $value = str_replace(["\r", "\n"], '', $value);

        // Never blank-out an existing secret via an empty form submission
        if ($value === '') {
            return;
        }

        $envPath = ROOTPATH . '.env';
        if (! file_exists($envPath)) {
            return;
        }

        $contents = file_get_contents($envPath);
        $escaped  = preg_quote($key, '/');
        $line     = $key . ' = ' . $value;

        if (preg_match('/^' . $escaped . '\s*=.*/m', $contents)) {
            $contents = preg_replace('/^' . $escaped . '\s*=.*/m', $line, $contents);
        } else {
            $contents .= PHP_EOL . $line;
        }

        file_put_contents($envPath, $contents);
    }
}
