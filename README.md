# Pubvana v2

A modern, open-source blogging and content management system built on CodeIgniter 4.

## Features

- **Posts & Pages** — full CRUD with draft/published/scheduled workflow
- **Dual content editor** — Summernote (WYSIWYG HTML) or SimpleMDE (Markdown), selectable per post
- **Theme system** — folder-based themes with widget areas, options, and asset symlinking
- **Widget system** — 8 built-in widgets, drag-and-drop area management
- **Configurable front page** — set the homepage to the blog index or any static page
- **Marketplace** — browse and install free themes & widgets; paid items link to the Pubvana store
- **Role-based access** — superadmin, admin, editor, author, subscriber (powered by CodeIgniter Shield)
- **Media library** — image upload with auto-generated thumbnails
- **Navigation manager** — primary and footer menus with drag-and-drop reordering
- **Comment moderation** — approve, spam, or trash comments
- **SEO** — per-post meta title/description, sitemap.xml, RSS feed, Google Analytics
- **Redirects** — manage 301/302 redirects from the admin panel
- **Social links** — manage social media links displayed site-wide

## Stack

| Layer | Technology |
|---|---|
| Framework | CodeIgniter 4.7 |
| Authentication | CodeIgniter Shield |
| Settings | codeigniter4/settings |
| Markdown | Parsedown |
| Admin UI | SB Admin 2 (Bootstrap 4 + jQuery) |
| Public theme | Bootstrap 5 + Font Awesome 6 |
| HTML editor | Summernote |
| Markdown editor | SimpleMDE |

## Requirements

- PHP 8.2 or higher
- MySQL 5.7+ / MariaDB 10.3+
- Composer
- Apache with `mod_rewrite` enabled (or Nginx equivalent)
- PHP extensions: `intl`, `mbstring`, `json`, `mysqlnd`, `gd`, `zip`

## Installation

```bash
# Clone the repo
git clone https://github.com/enlivenapp/pubvana.git
cd pubvana

# Install dependencies
composer install

# Copy and configure environment
cp env .env
# Edit .env: set app.baseURL, database credentials, CI_ENVIRONMENT

# Generate encryption key
php spark key:generate

# Run migrations
php spark migrate --all

# Seed default data (theme, widgets, settings, superadmin user)
php spark db:seed DatabaseSeeder

# Point your web server DocumentRoot to /path/to/pubvana/public
```

## Default Admin Credentials

After seeding, log in at `/login`:

| Field | Value |
|---|---|
| Email | `admin@example.com` |
| Password | `Admin@12345` |

**Change these immediately after first login.**

## Directory Structure

```
pubvana/
├── app/                    # CI4 application (controllers, models, views, config)
├── public/                 # Web root (index.php, assets)
│   ├── assets/admin/       # SB Admin 2 vendored files
│   └── themes/             # Symlinked theme assets (auto-created)
├── themes/                 # Installed themes
│   └── default/            # Built-in Bootstrap 5 theme
├── widgets/                # Installed widgets (8 built-in)
├── writable/               # Cache, logs, sessions, uploads
└── vendor/                 # Composer dependencies (not committed)
```

## Built-in Widgets

| Widget | Description |
|---|---|
| Recent Posts | Latest posts with optional date/excerpt |
| Categories List | All categories with post counts |
| Tag Cloud | Tag cloud with configurable max tags |
| Social Links | Site social media links |
| Text Block | Free HTML/text content |
| Search Form | Site search input |
| Recent Comments | Latest approved comments |
| Archive List | Monthly or yearly post archives |

## Contributing

Issues and pull requests are welcome at [github.com/enlivenapp/pubvana](https://github.com/enlivenapp/pubvana).

## License

MIT
