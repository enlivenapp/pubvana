# Pubvana

### Blogging and Small Business CMS

Pubvana is a re-brand of Open Blog v3 (with added functionality). v2 is a full rewrite on CodeIgniter 4 with a modern admin UI, dual content editor, theme & widget system, and built-in marketplace.

## Installation

```bash
git clone https://github.com/enlivenapp/pubvana.git
cd pubvana
composer install
cp env .env
# Edit .env: set app.baseURL, database credentials, CI_ENVIRONMENT
php spark key:generate
php spark migrate --all
php spark db:seed DatabaseSeeder
```

Point your web server `DocumentRoot` at the `public/` folder.

**Default admin login** — `admin@example.com` / `Admin@12345` — change immediately after first login.

> **Theme assets symlink**
> After installation, activate your chosen theme via **Admin → Themes**. This automatically creates the symlink `public/themes/{folder}` → `themes/{folder}/assets` so CSS, JS, and images are served correctly. If you deploy to a server where the symlink is missing (e.g. after a fresh `git clone`), either re-activate the theme in the admin or run:
> ```bash
> ln -s /path/to/pubvana/themes/default/assets /path/to/pubvana/public/themes/default
> ```

## Requirements

- PHP 8.2+
- MySQL 5.7+ / MariaDB 10.3+
- Composer
- Apache `mod_rewrite` (or Nginx equivalent)
- PHP extensions: `intl`, `mbstring`, `json`, `mysqlnd`, `gd`, `zip`

## Stack (v2)

| Layer | Technology |
|---|---|
| Framework | CodeIgniter 4.7 |
| Authentication | CodeIgniter Shield |
| Admin UI | SB Admin 2 (Bootstrap 4 + jQuery) |
| Public theme | Bootstrap 5 + Font Awesome 6 |
| HTML editor | Summernote |
| Markdown editor | SimpleMDE |

## Features (v2)

- Posts & Pages with draft/published/scheduled workflow
- Dual content editor — WYSIWYG HTML or Markdown, selectable per post
- Theme system with widget areas, theme options, and asset symlinking
- 8 built-in widgets with drag-and-drop area management
- Configurable front page — blog index or any static page
- Marketplace — browse and install free themes & widgets (live API + cache + mock fallback)
- Role-based access — superadmin, admin, editor, author, subscriber
- Media library with auto-generated thumbnails
- Navigation manager with drag-and-drop reordering
- Comment moderation — approve, spam, or trash
- SEO — per-post meta, sitemap.xml, RSS feed, Google Analytics
- 301/302 redirect manager
- Social links manager
- Author profiles with bio cards on posts
- Social OAuth login (Google, Facebook)
- Social auto-share on publish (Twitter, Facebook)
- WordPress importer (admin UI + `php spark wp:import` CLI)
- Post revision history with one-click restore

## Bug Reports & Feature Requests

Please use the [Issues Tracker](https://github.com/enlivenapp/pubvana/issues).

## Links

[pubvana.net Home](http://pubvana.net)

Pubvana Addon Store (Themes, Widgets, and other Addons) — Coming Soon

[Facebook Page](https://www.facebook.com/pubvana.net)

[User Docs](http://pubvana.net)

## License

Pubvana is released under the MIT Open Source License.

## Contributors & Team Members

- Enliven Applications

## Translators & Translations

_Translators Wanted!_

If you would like to help translate files, please fork this repo and send a PR.

* French, Indonesian, and Portuguese need updates.

Please include a README.md update under 'Translators' with your name and a link to your site/GitHub (optional).

* French
  - [Paul DUBOT](https://github.com/keeganpa)
  - [Léonard GAURIAU](https://github.com/leoDisjonct)
  - [Clément TRASSOUDAINE](https://github.com/intv0id)
  - [Jean-Baptiste VALLADEAU](https://github.com/ignamarte)
  - [Rhagngahr](https://github.com/Rhagngahr)

* Indonesian
  - [Suhindra](https://github.com/suhindra)

* Portuguese
  - [Samuel Fontebasso](https://github.com/fontebasso)

## Roadmap / Todo

### Widgets
- [x] Recent Posts
- [x] Tag Cloud
- [x] Categories List
- [x] Archive List
- [x] Search Form
- [x] Social Links
- [x] Text Block
- [x] Recent Comments
- [x] Table of Contents
- [x] Related Posts
- [ ] Author Bio (sidebar)
- [ ] Ad Unit / Custom HTML
- [ ] Social Follow Buttons
- [ ] Reading Progress Bar
- [ ] Enhanced Search (AJAX live preview)
- [ ] Email Opt-in / Lead Capture
- [ ] Countdown Timer

### Admin / Platform
- [x] Author Profiles & Bio Card
- [x] Social OAuth Login (Google, Facebook)
- [x] Social Auto-Share on Publish (Twitter, Facebook)
- [x] Marketplace API with cache + refresh
- [x] WordPress Importer (UI + CLI)
- [x] Post Revision History
- [ ] Scheduled Post Queue (calendar view)
- [ ] Content Preview Links (shareable draft URLs)
- [ ] Bulk Post Actions (publish/unpublish/delete many)
- [ ] Schema.org Markup (Article, BreadcrumbList, Author JSON-LD)
- [ ] Broken Link Checker
- [ ] Image WebP Auto-Convert on Upload
- [ ] Two-Factor Authentication (TOTP admin UI)
- [ ] Backup & Export (DB + uploads zip)
- [ ] Activity / Audit Log
- [ ] Maintenance Mode toggle

### Monetisation
- [ ] Membership / Paywalled Posts
- [ ] Tip Jar / Per-post donations
- [ ] Affiliate Link Manager (/go/ short links)
- [ ] E-commerce (products, cart, checkout, orders)

### Premium Widgets (pubvana.net/store)
- [ ] Advanced Login
- [ ] Gallery (masonry + lightbox)
- [ ] Google Calendar & Maps
- [ ] YouTube Channel Feed
