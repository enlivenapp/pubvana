# AGENTS.md — Comments plugin

Guidance for AI agents contributing to this plugin, which ships inside the main Pubvana repo.

## Overview

Comments provides nested, moderated site comments. Captcha on the comment form is delegated to the core CaptchaService (configured site-wide under Settings > Captcha, with the "Comment form" switch). Content plugins register themselves as comment hosts through the adext `comments.host` slot; this plugin renders a thread and reply form inside any host's public view.

- **Package:** `pubvana/comments` (`pubvana.json:2`), semver `0.1.0`, category `content`
- **License:** MIT, matching the main project (repo `composer.json` declares `"license": "MIT"`)
- **PHP floor:** not declared in the plugin; the main project requires PHP `^8.2` (repo `composer.json`), and the code stays within that floor (`str_contains` at `Controllers/CommentsPublicController.php:115`, `mixed` parameter/return types at `Services/CommentService.php:65`, arrow functions at `Plugin.php:121`)
- **Namespace:** `Pubvana\Plugins\Comments` (`Plugin.php:5`), with `Controllers`, `Services`, `Models`, and `Database\Migrations` sub-namespaces
- **Runtime dependencies (declared at the app level, not in the plugin):** `flightphp/active-record` (model base), `enlivenapp/migrations` (migration base), `enlivenapp/flight-shield` (`\Enlivenapp\FlightShield\Models\User` at `Services/CommentService.php:674, 793`), `ezyang/htmlpurifier` (optional, guarded by `class_exists` at `Services/CommentService.php:747`); core engine services used as `$app->comments()`, `db()`, `adext()`, `pluginLoader()`, `settings()`, `view()`, `request()`, `session()`, `captcha()`; core helper functions `user_id()` and `csrf_field()`; core class `\Pubvana\Services\PluginView` (`Services/CommentService.php:283`)
- **Settings storage:** database `settings` table under the `Comments.*` namespace, seeded in `Database/Seeds/Seed.php`
- **Docs:** `README.md`

## Project guidelines

1. **Only store comment bodies that have passed HTMLPurifier.** `create()` always purifies `body` before insert (`Services/CommentService.php:210-213`). Reason: comment text is untrusted visitor input and must not reach templates unsanitized.
2. **Never hardcode a setting key in controllers or views.** Read all configuration through `CommentService::setting()` (which prefixes `Comments.`, `Services/CommentService.php:65-68`) and write it through `$app->settings()->set('Comments.*', ...)` in `settingsSave()`. Reason: settings live in the database and can be overridden; the `Comments.` prefix is the single source of truth.
3. **Treat comment hosts as opt-in.** A host only renders when its adext key is in the `Comments.enabledHosts` JSON setting (`Services/CommentService.php:504-541`). New hosts start closed. Reason: hosts must be explicitly enabled by an admin before accepting visitor content.
4. **Enforce the nesting limit on every reply.** `create()` checks `getDepth()` against `max_nesting_depth` (default 3) and throws before insert (`Services/CommentService.php:187-195`). Reason: unbounded threading makes threads unreadable and the model walk expensive.
5. **Route all host lookups through the `comments.host` adext slot.** `hostItems()`, `hostItem()`, `hostTypeMap()`, and `enabledTypes()` all resolve hosts from registered `comments.host` contributions and cache them per request (`Services/CommentService.php:410-647`). Reason: hosts are other plugins by design; the adext registry is the only allowed discovery path and the caches stop per-comment SELECT storms.
6. **Do not bypass the public gating chain in `dataFor()`.** A thread must render nothing when the system is disabled, the host type is not enabled, or the item disallows comments (`Services/CommentService.php:356-399`). Reason: an empty string is the contract hosts rely on to decide whether to inject anything.
7. **Delegate captcha to the core CaptchaService.** `create()` checks `CaptchaService::enforcedFor('comments')` (provider configured plus the "Comment form" switch on in Settings > Captcha) and calls `verify()`; the service itself fails closed on a missing secret or an unreachable provider. Templates render the widget with the `captcha` Vision tag, never with hardcoded provider markup. Reason: captcha config is site-wide (shared with forms and the sign-in form); duplicating provider logic here would drift.
8. **Keep guest attribution split from user attribution.** Comments store either `user_id` or `guest_name`/`guest_email`/`guest_website`, never both (`Controllers/CommentsPublicController.php:87-98`). Reason: the display and admin tooling branch on this split.
9. **Do not add soft deletes to comments.** `delete()` is a hard delete (`Models/Comment.php:164-174`). Descendants are not cascaded; `buildTree()` promotes orphaned children to the thread root (`Services/CommentService.php:769-777`). Reason: moderation is explicit and a visitor comment must actually disappear, not linger as a tombstone.
10. **Keep the 3-tier template resolution intact.** `resolveTemplate()` resolves `pubvana/comments/public/comments.tpl` as app/Views override, then theme, then plugin (`Services/CommentService.php:308-344`). Reason: it matches `RegionManager::resolveBlockTemplate` and appends `.tpl` explicitly because `PluginView`'s mutable extension may still be `.php` on early render.

## Repository layout

```
plugins/Comments/
├── Config/Config.php                  routePrepend: 'comments'; configPrepend: 'pubvana.comments'
├── Controllers/
│   ├── CommentsAdminController.php    Moderation queue + settings/host-manager (extends core AdminController)
│   └── CommentsPublicController.php   Submission endpoint + standalone fallback page (extends core PublicController)
├── Database/
│   ├── Migrations/2026-08-26-100002_CreateCommentsTable.php
│   │                                  comments (commentable_type/id, parent_id, user/guest cols,
│   │                                  status, ip_address; indexes on host pair, parent, status, user)
│   └── Seeds/Seed.php                 Seed: 5 "Comments.*" settings rows + comments.moderate permission
├── Models/Comment.php                 comments table; find, paginate, count, status updates, depth walk
├── Services/CommentService.php        Singleton mapped as $app->comments() (Plugin.php:28-34); lifecycle owner
├── assets/css/comments.css            Public styles (registered via adext public.css)
├── Plugin.php                         Entry point; routes, dashboard, block, host-manager glue
├── pubvana.json                       Manifest; provides admin.menu (Manage/Settings) and admin.dashboard
├── Views/
│   ├── admin/                         index, show, settings (config fields + per-host toggles)
│   ├── public/comments.tpl            Injectable thread + form fragment (rendered by CommentService::render())
│   ├── public/blocks/recent-comments.tpl   Recent Comments block
│   └── comments.tpl                   Standalone display template (core render('comments'))
└── README.md
```

## Core architecture

**Entry point.** `Plugin::register()` (`Plugin.php:25`). Maps the `comments` singleton `Services\CommentService` (built from `$app->db()` and the engine, `Plugin.php:28-34`), then registers via adext.

**Extension points (adext registrations).**
- Admin routes under `pubvana.comments` (`Plugin.php:41-49`): moderation list, settings page, show, approve, reject, delete.
- Public routes under the `pubvana/comments` route prefix (`Plugin.php:55-58`): `GET/POST /{prefix}/@type/@id`.
- `admin.dashboard` card (pending count) and section (pending list) (`Plugin.php:62-114`).
- `block.available` recent-comments (`Plugin.php:118-128`).

**Host contract (outbound).** Content plugins register `comments.host` with a `callable` returning `{type, id, title, url, allow_comments}` items. `CommentService` consumes that catalog for the admin host manager, the recent-comments block, and host-style enrichment (`Services/CommentService.php:410-647`).

**Render pipeline (inbound).** A host calls `CommentService::render($type, $id, $allowComments)` or `dataFor()`; `render()` builds the view data, resolves the `.tpl` through the 3-tier override chain, and renders it (`Services/CommentService.php:267-344`). The result is injected into the host's template as `comments_html`.

**Submission path.** `CommentsPublicController::store()` gates on system enabled, host type enabled, guest policy, empty body, and guest name, then delegates to `CommentService::create()` which additionally enforces nesting depth, captcha, and purification (`Controllers/CommentsPublicController.php:41-108`). Errors bounce back to the referrer with a `comment_error` query flag; `dataFor()` re-reads it and the template renders it as `comments_error`.

**Display path.** `findByContent()` only returns approved comments (`Models/Comment.php:48-60`); they are threaded by `buildTree()` and flattened with depth one level at a time by `flattenComments()` (`Services/CommentService.php:757-814`).

## Development and testing

This plugin has no `composer.json` and no test suite, unlike library plugins in the Pubvana repo. It is exercised through the full app.

- Lint/static analysis (app-wide, from the repo root; the plugin ships in-tree):
  - `vendor/bin/phpstan analyse` (level 3, sees `app/` plus `scanDirectories: vendor/`; ignored-error baseline covers the migration/activerecord internals)
  - `find plugins/Comments -name '*.php' -exec php -l {} \;`
- Manual verification checklist:
  - [ ] Post a comment as a guest and as a logged-in user; confirm the guest form fields appear only for guests
  - [ ] Reply below the nesting limit works; replying at depth >= `max_nesting_depth` fails with the nesting message
  - [ ] With a host enabled, the thread renders; toggle the host off in settings and confirm the thread disappears
  - [ ] With guest comments off, logged-out visitors see existing comments but no form; the store endpoint redirects with `comment_error`
  - [ ] Approve, reject, and delete a comment from the moderation queue; only approved items show publicly
  - [ ] Turn on the "Comment form" switch under Settings > Captcha with a configured provider and confirm the widget renders in the form and unverified submissions are rejected; turn the switch off and confirm submissions pass without a token
  - [ ] Delete a comment with replies and confirm orphaned replies still render at the top level
  - [ ] Confirm the recent-comments block lists approved comments linked back to their host content

No coverage is configured for this plugin. `<!-- TODO: add [coverage target] -->`

## Coding standards
- **PHPStan (level 8):** every model carries `@property`/`@method` annotations for its columns and the ActiveRecord magic it uses, and every service facade has a `@phpstan-method` entry in `phpstan-stubs.php`. Run `composer phpstan` before committing.

1. **`declare(strict_types=1);` at the top of every class file** (`Plugin.php:3`). No exceptions.
2. **Models extend `Pubvana\Models\AbstractModel` and declare their table string in the constructor** (`Models/Comment.php:28-33`).
3. **Prefer the ActiveRecord fluent query; raw SQL only for `GROUP BY` aggregates.** `countByType()` is the single raw query and uses named placeholders for status (`Models/Comment.php:100-122`).
4. **Use `DateTimeImmutable` for all timestamp writes** (`Models/Comment.php:129, 155`). Do not call `date()` for stored values.
5. **Keep views dumb and dependency-free.** The injectable partial consumes the `dataFor()` array verbatim (`Views/public/comments.tpl`); do not call services from templates.
6. **Catch `\Throwable` only at trust boundaries.** Host callables and template rendering already fail soft to empty output (`Services/CommentService.php:293-297, 422-426, 632-635`); do not add blanket try/catch inside business logic.

## Documentation sources

| Source | Purpose |
|--------|---------|
| `README.md` | User and plugin-author docs: host registration shape, `render()`/`dataFor()` usage, opt-in host management, moderation permission |
| `Plugin.php:13-22` | Plugin purpose and the `comments.host` contract description |

## Common tasks

| Goal | Where to look |
|------|---------------|
| Add a comment setting | `Database/Seeds/Seed.php` settings row, `Services/CommentService.php` getter, `Controllers/CommentsAdminController.php` `settingsIndex()`/`settingsSave()` |
| Add a captcha provider | `app/Services/CaptchaService.php` (`PROVIDERS` map), plus the provider select options in `app/config/core-admin.php` and `CaptchaAdminController` |
| Change comment captcha behavior | Settings > Captcha (provider, keys, "Comment form" switch); enforcement point is `create()` calling `enforcedFor('comments')` |
| Change the nesting limit | `Comments.max_nesting_depth` setting (database, default 3) |
| Change moderation page pagination | `Controllers/CommentsAdminController.php:31` |
| Register a new host side | Read `README.md` "Registering a host" (host plugin's own `Plugin.php`) |
| Change the block output shape | `recentCommentsBlock()` (`Services/CommentService.php:661-693`) + `Views/public/blocks/recent-comments.tpl` |
| Add an admin moderation action | Route in `Plugin.php:41-49`, action in `CommentsAdminController.php`, service method in `CommentService` |

## PR / contribution checklist

- [ ] Every claim in changed code is grounded in the actual plugin code; no guessing at behavior
- [ ] `declare(strict_types=1)` present; no em dashes in new prose; one-line reasons preserved on any edited guideline
- [ ] PHP syntax verified (`php -l`) and PHPStan level 3 is clean on the app
- [ ] New write paths purify `body`, enforce nesting depth, and surface errors via `comment_error`
- [ ] Host gating (enabled system, enabled type, item opt-in, guest policy) honored for any new render/form path
- [ ] Settings read only via `CommentService::setting()`; nothing hardcoded outside the `Comments.*` prefix
- [ ] No soft deletes introduced; no changes to the 3-tier template resolution
- [ ] README updated only if user-facing behavior changed

## Out of scope / non-goals

- This is an in-tree application plugin, not a Composer package; no `composer.json` and nothing for Packagist.
- No email notifications for new comments or replies.
- No spam engine beyond optional captcha plus human moderation; no rate limiting.
- No pagination or lazy loading of threads; the full approved tree loads per item render.
- No localization; labels and messages are hardcoded English.
