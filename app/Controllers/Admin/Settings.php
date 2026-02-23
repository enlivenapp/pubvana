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
        $fpId   = $this->request->getPost('front_page_id');
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
}
