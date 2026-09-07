# Default Theme

The default Pubvana theme. A clean Bootstrap 5 site theme built on the Bootswatch Flatly palette, providing a navbar, optional hero, breadcrumbs, a sidebar region, a multi-column footer, and full-page templates for every public content type.

## What It Does

- Renders all public content through a single master layout: navbar, hero (optional), breadcrumbs (optional), page content, sidebar blocks, and footer.
- Provides a full set of page templates for the blog, static pages, search, categories, tags, and user profiles.
- Exposes a configurable **sidebar** region and three **footer column** regions so site owners place blocks where they want.
- Supplies its own Bootstrap 5 assets (CSS and JS), served automatically at `/assets/theme/default/...`.
- Ships with three ready-made block templates (HTML content, Recent Posts, Tag Cloud) for use in the region system.

## Theme Options

Options are managed in **Admin > Appearance > Themes > Options**. They are grouped in the admin form; the group name only affects the form layout, not how each option behaves.

| Group | Option | Type | Default | Purpose |
|-------|--------|------|---------|---------|
| Layout | Homepage Layout | select | `full-width` | Layout of the homepage when it is a static page: `full-width`, `sidebar-right`, `sidebar-left` |
| Layout | Blog Layout | select | `sidebar-right` | Sidebar placement on all blog listings and single posts: `sidebar-right`, `sidebar-left` |
| Breadcrumbs | Show Breadcrumbs | toggle | on | Show the auto-generated breadcrumb trail on subpages |
| Hero | Show Hero | toggle | off | Show the hero section below the navbar |
| Hero | Background Image | media | (none) | Background image for the hero section |
| Hero | Title | input | (none) | Title text displayed in the hero |
| Footer Bottom | Footer Bottom | toggle | off | Show the bottom strip of the footer with the copyright line and `<hr>` |
| Footer Bottom | Footer Text | input | (none) | Custom copyright/site text; falls back to the site copyright setting when blank |

### Layout behavior

- **Homepage Layout** applies only when the site homepage is set to a static page. A blog-list homepage always follows **Blog Layout** instead.
- **Blog Layout** governs every blog page: the blog homepage, `/blog`, single posts, archives, category and tag listings.
- Standalone (non-homepage) static pages always render full width.

## Regions

Regions are where site owners place content blocks. Managed in **Admin > Appearance > Themes > Regions**.

| Region ID | Label | Where it renders |
|-----------|-------|------------------|
| `sidebar` | Sidebar | Right or left column next to blog content (placement follows Blog Layout) |
| `footer-col-1` | Footer Column 1 | First column of the footer |
| `footer-col-2` | Footer Column 2 | Second column of the footer |
| `footer-col-3` | Footer Column 3 | Third column of the footer |

The theme also uses the platform regions (`navbar`, `header`, `before-content`, `after-content`, `footer`) provided by the core.

## Templates

The theme includes a template for every public view. Templates are Vision `.tpl` files and never execute PHP.

| Template | Used for |
|----------|----------|
| `layout.tpl` | Master page shell (html, head, navbar, hero, breadcrumbs, content, footer) |
| `home.tpl` | Blog-list homepage and `/blog` |
| `page.tpl` | Static pages, including a page-based homepage |
| `post.tpl` | Single blog post with comments |
| `archive.tpl` | Blog category and tag archive listings |
| `categories.tpl` | Category index |
| `tags.tpl` | Tag index |
| `search.tpl` | Search results |
| `profile.tpl` | Public user profile |
| `profile_edit.tpl` | Profile editing form |
| `partials/` | Reusable fragments: navbar, footer, hero, breadcrumbs, pagination, post list, comments |
| `blocks/` | Block templates: HTML content, Recent Posts, Tag Cloud |
| `enlivenapp/flight-shield/` | Auth screen overrides: Shield login/register/2FA/activation/magic-link pages, auth email bodies, and the forgot/reset password pages (`auth/`) |

## Assets

- CSS: `assets/css/bootstrap.min.css`, `assets/css/pubvana.css`
- JS: `assets/js/bootstrap.bundle.min.js`
- Icon: `icon.svg` (shown in the admin theme picker)

Assets are served by the AssetService at `/assets/theme/default/{path}`; they are read from the theme's `assets/` folder and never copied into `public/`.

## See Also

- `docs/` — build and extension documentation, including **docs/Themes.md** for creating a new theme.
