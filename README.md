<p align="center">
  <img src="pubvana-nodrop-nobg.png" alt="Pubvana CMS" width="220">
</p>

# Pubvana CMS

Pubvana v3 is a full-featured CMS built on the [FlightPHP](https://github.com/flightphp) microframework with shared-host friendly setup for personal blogs, small to medium business websites, company intranets and more. We have completely rebuilt version 3 with a new, fresh administration panel, plugin extensions, faster internals, and more included free features than you\'d expect in a free CMS.

[![License: MIT](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://www.php.net)

## v2
[![Latest Release](https://img.shields.io/github/v/release/Pubvana-CMS/pubvana)](https://github.com/Pubvana-CMS/pubvana/releases)
[![Codeigniter 4](https://img.shields.io/badge/Codeigniter4-4.7-orange.svg)](https://codeigniter.com)
[![status](https://img.shields.io/badge/status-maintenance-blue)](https://github.com/Pubvana-CMS/pubvana)

## v3
[![v3](https://img.shields.io/badge/v3-In%20Development-blue)](https://github.com/Pubvana-CMS/pubvana)
[![Latest Unstable Version](http://poser.pugx.org/enlivenapp/pubvana/v/unstable)](https://packagist.org/packages/enlivenapp/pubvana) 
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://www.php.net)

[![FlightPHP](https://img.shields.io/badge/FlightPHP-3.0-orange.svg)](https://flightphp.com)
![PHPStan: Level 8](https://img.shields.io/badge/PHPStan-level%208-brightgreen.svg?style=flat)
[![PHPStan](https://github.com/Pubvana-CMS/pubvana/actions/workflows/phpstan.yml/badge.svg)](https://github.com/Pubvana-CMS/pubvana/actions/workflows/phpstan.yml)
[![Psalm](https://github.com/Pubvana-CMS/pubvana/actions/workflows/psalm.yml/badge.svg)](https://github.com/Pubvana-CMS/pubvana/actions/workflows/psalm.yml)
[![Tests](https://github.com/Pubvana-CMS/pubvana/actions/workflows/test.yml/badge.svg)](https://github.com/Pubvana-CMS/pubvana/actions/workflows/test.yml)

[![Daily Downloads](http://poser.pugx.org/enlivenapp/pubvana/d/daily)](https://packagist.org/packages/enlivenapp/pubvana)
[![Monthly Downloads](http://poser.pugx.org/enlivenapp/pubvana/d/monthly)](https://packagist.org/packages/enlivenapp/pubvana)
[![Total Downloads](http://poser.pugx.org/enlivenapp/pubvana/downloads)](https://packagist.org/packages/enlivenapp/pubvana) 

---

## Notice: 
v3 is currently in Alpha developement. Thing change rapidly, and untested update ability is available. If you need a stable release *right now* consider [Pubvana v2](https://github.com/Pubvana-CMS/pubvana/releases/tag/v2.3.6).  Thanks for considering Pubvana!

---

## Installation

See [Install](INSTALL.md)


## Features

- Posts and pages with draft/published/scheduled workflow
- WYSIWYG editor (Jodit) for all content types
- Plugin system with enable/disable from the admin panel
- Vision templates for public pages (no PHP execution)
- Region and block system with drag-and-drop placement
- Media library with image editing
- Built-in SEO (meta, sitemaps, schema, LLMs.txt)
- Comment system with moderation
- URL redirect manager with 404 tracking
- Form builder with submissions
- Server-side analytics with daily rollups
- Search across all content types
- Role-based access control (Shield)
- SMTP email with encrypted credentials
- Dark mode admin UI (Tabler/Bootstrap 5)


## Project Structure

```
pubvana/
  app/
    config/             - Bootstrap, routes, services, env handling
    Controllers/        - Admin and public controllers
    Database/           - Core migrations and seeds
    Models/             - Core models
    Services/           - ExtensionRegistry, PluginLoader, PluginView, etc.
    Views/admin/        - Admin templates (.php)
  plugins/              - One folder per plugin
  themes/               - Theme folders with Vision templates
```

## Developer Documentation

- Coming Soon


## Stack

| Layer | Technology |
|---|---|
| Framework | FlightPHP 3.0 |
| Authentication | enlivenapp/flight-shield |
| Database | enlivenapp/flight-active-record |
| Admin UI | Tabler (Bootstrap 5 + Alpine.js) |
| Public Templates | Vision (no PHP execution) |
| Content Editor | Jodit |
| Mail | PHPMailer |

## Security

### Reporting a Vulnerability

See [Security](SECURITY.md) Report at [GitHub Security Advisories](https://github.com/Pubvana-CMS/pubvana/security/advisories/new) to report them privately.

### Production Hardening

- Set `APP_ENV=production` in `.env`
- Set `FORCE_HTTPS=true` in `.env`
- Use a strong password for your admin account
- Set `SITE_URL` to your actual domain
- Ensure `.env` has permissions `600` and is not committed to version control
- Point your web server's DocumentRoot to `public/`

## Bug Reports and Feature Requests

Use the [Issues Tracker](https://github.com/Pubvana-CMS/pubvana/issues).

## Links

[pubvanacms.com](https://pubvanacms.com)

## License

MIT. [LICENSE](LICENSE.md)

## Contributors

- Enliven Applications

- [FlightPHP](https://github.com/flightphp):  While the folks over at flightPHP hasn't contributed code to this project directly, they have provided the basis of the project with `core`, `active record` and others. Consider using FlightPHP for your next project! 

## Legacy

- Orginally brought to github by [Kami](https://github.com/Kami) as [Open Blog](https://github.com/Kami/Open-Blog) around 2010 built on Codeigniter (2-ish).
- Dec. 2016 Kami released [Open Blog](https://github.com/enlivenapp/Open-Blog) to [Enlivenapp](https://github.com/enlivenapp) along with domains.
- Enlivenapp brought about [Open Blog 3](https://github.com/enlivenapp/Open-Blog-3) updated with Codeigniter3 and more functionality.
- Mar 2018 some copyright issues caused the renaming of Open Blog to Pubvana and was release under Enlivenapp/pubvana through Codeigniter4 until the Aug of 2026.
- Aug 2026 Pubvana was moved to it's own organisation(github) where [Pubvana v3](https://github.com/Pubvana-CMS/pubvana) left behind Codeigniter and began in ernest on a new framework called [FlightPHP](https://github.com/flightphp) with updated and modern coding style, security, and a larger feature set. This is a completely different codebase and is very much not backwards compatable to previous versions of Pubvana and Open Blog. 