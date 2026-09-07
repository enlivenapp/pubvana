# AGENTS.md — Forms plugin

Guidance for AI agents contributing to this plugin, which ships inside the main Pubvana repo.

## Overview

Forms is a form builder for Pubvana: admins build forms with a JSON field-definition schema, embed them on the public site via shortcodes, a region block, or direct service calls, and review stored submissions.

- **Package:** `pubvana/forms` (`pubvana.json:2`), semver `0.1.1`, category `content`
- **License:** MIT, matching the main project (repo `composer.json` declares `"license": "MIT"`)
- **PHP floor:** not declared in the plugin; the main project requires PHP `^8.2` (repo `composer.json`), and the code stays within that floor (`match` at `Services/FormsService.php:308`, `str_starts_with` at `Services/FormsService.php:477`, `mixed` types)
- **Namespace:** `Pubvana\Plugins\Forms` (`Plugin.php:5`), with `Controllers`, `Services`, `Models`, and `Database\Migrations` sub-namespaces
- **Runtime dependencies (declared at the app level, not in the plugin):** `flightphp/active-record` (model base), `enlivenapp/migrations` (migration base), `ezyang/htmlpurifier` (optional, guarded by `class_exists` at `Services/FormsService.php:697`), PHPMailer through the core mailer (`$this->app->mailer()->sendHtml()`, `Services/FormsService.php:630`); core engine services used as `$app->forms()`, `db()`, `adext()`, `pluginLoader()`, `request()`, `session()`, `slugify`, `redirect`, `captcha()`; core helper `csrf_field()`; config value `flight.base_url`
- **Config:** `Config/Config.php`: `routePrepend` (`forms`), `per_page` (25), `submissions_per_page` (25), `rate_limit_seconds` (10)
- **Docs:** `README.md`

## Project guidelines

1. **Route every form write through `FormsService`.** `createForm()` / `updateForm()` are the only entry points that keep `forms` and `form_fields` consistent (`Services/FormsService.php:64-89`). Reason: the field sync step cannot be skipped or the stored field set and the rendered form diverge.
2. **Field definitions are a delete-then-reinsert sync, never a diff.** `syncFields()` drops all rows for the form and inserts the submitted list in order with `sort_order = index + 1` (`Services/FormsService.php:505-527`). Reason: reordering is how field order is persisted, and the reshuffle is cheaper than tracking per-field changes.
3. **Render public forms as inline HTML strings, not templates.** `renderPublicForm()` builds the whole `<form>` in PHP, escaping every interpolated value with `htmlspecialchars` (`Services/FormsService.php:219-334`). Reason: form markup must echo the stored field set exactly, and no template layer is involved.
4. **Never render or accept submissions for non-published forms.** The public submit route, the block provider, and the shortcode resolver all gate on `status === 'published'` (`Controllers/FormsPublicController.php:21`, `Services/FormsService.php:359-376, 535-562`). Reason: drafts are internal artifacts and must stay off the public surface.
5. **Keep the spam pipeline order unchanged: honeypot, then rate limit, then captcha (when the forms area is protected), then validation.** A filled honeypot `website` field drops the submission silently and reports success (`Services/FormsService.php:383-386`); the session rate limit fails closed; captcha delegates to the core CaptchaService (`enforcedFor('forms')` + `verify()`, configured under Settings > Captcha) and reports failure like any validation error; validation runs per field type with server-side allowed-value checks for `select`/`radio`/`checkbox` (`Services/FormsService.php:388-438`). Reason: bots should not be able to detect the trap, and clients must never be trusted to send clean values.
6. **Sanitize by field type in `sanitizeScalarValue()`.** `textarea` goes through HTMLPurifier (fallback `strip_tags`), `email` through `FILTER_SANITIZE_EMAIL`, everything else is trimmed and `strip_tags`-ed (`Services/FormsService.php:672-693`). Reason: submission payloads are stored raw in JSON and later echoed in the admin; they must arrive clean.
7. **Never let a mail failure break a submission.** `dispatchNotifications()` swallows every `\Throwable` from the mailer (`Services/FormsService.php:628-634`). Reason: an SMTP hiccup must not lose a visitor's submission.
8. **Always pass `_return_url` through `normalizeReturnUrl()` before redirecting.** It strips an allowed `flight.base_url` prefix, rejects foreign absolute URLs, and forces a leading `/` (`Services/FormsService.php:467-489`). Reason: raw referrer/return values would otherwise be an open-redirect vector.
9. **Ship form styles through adext `public.css`, never inline.** Public CSS lives in `assets/css/forms.css` and registers in `Plugin.php` as type `public.css` (served at `/assets/plugin/Forms/css/forms.css`). Reason: direct `plugins/` access is blocked by `.htaccess`, but AssetService serves `assets/` URLs, so an inline `<style>` block only escapes browser caching and loses to the theme's stylesheet overrides.
10. **Use the per-form session flash for error/values round-trips.** `storeSubmissionFlash()`/`consumeSubmissionFlash()` key `pubvana_forms_flash[formId]` (`Services/FormsService.php:491-503`), read back on the next render to repopulate the form (`Services/FormsService.php:221-228`). Reason: redirects after POST keep validation state without re-posting.
11. **Start sessions defensively in the service.** `startSession()` only starts when no session is active and headers are not sent (`Services/FormsService.php:637-642`). Reason: the service may run before the core session layer initializes.

## Repository layout

```
plugins/Forms/
├── Config/Config.php                  routePrepend, per_page, submissions_per_page, rate_limit_seconds
├── Controllers/
│   ├── FormsAdminController.php       Form CRUD (extends core AdminController)
│   ├── FormSubmissionsAdminController.php  Submission listing/detail
│   └── FormsPublicController.php      POST /{prefix}/submit/@id (extends core PublicController)
├── Database/
│   ├── Migrations/
│   │   2026-08-29-100001_CreateFormsTable.php        forms (slug unique, status enum, soft-delete cols)
│   │   2026-08-29-100002_CreateFormFieldsTable.php   form_fields (type/name/label, options_json, sort_order)
│   │   2026-08-29-100003_CreateFormSubmissionsTable.php  form_submissions (IP/UA/referrer, payload_json)
│   └── Seeds/Seed.php                 Seed: forms.manage permission + draft Contact form with 3 fields
├── Models/
│   ├── Form.php                       forms; find/paginate/count, whitelisted updateRecord, softDelete
│   ├── FormField.php                  form_fields; forForm ordered, deleteForForm
│   └── FormSubmission.php             form_submissions; find/paginate/count, dedicated timestamps
├── Services/FormsService.php          Singleton mapped as $app->forms() (Plugin.php:24-30); lifecycle owner
├── assets/css/forms.css               Public styles (registered via adext public.css)
├── Plugin.php                         Entry point; routes, dashboard, content.render hook, block
├── pubvana.json                       Manifest; provides admin.menu (Forms/Submissions) and admin.dashboard
├── Views/
│   ├── admin/                         index, create, edit, submissions, submission
│   └── public/blocks/form.tpl         Block wrapper (title + raw content)
└── README.md
```

## Core architecture

**Entry point.** `Plugin::register()` (`Plugin.php:22`). Maps the `forms` singleton `FormsService` (built from `$app->db()`, the engine, and config, `Plugin.php:24-30`), then registers via adext.

**Extension points (adext registrations).**
- Admin routes under `pubvana.forms` (`Plugin.php:38-48`): form CRUD plus submission listing/detail.
- Public route `POST /{prefix}/submit/@id` (`Plugin.php:52-54`).
- `admin.dashboard` cards: total forms, published count, submission count (`Plugin.php:58-62`).
- `content.render` shortcode hook, priority 40 (`Plugin.php:66-70`): rewrites `{% forms slug 'contact' %}` / `{% forms id 123 %}` and the attribute-style `{{ forms: id 1 }}` / `{{ forms: slug "contact" }}` inside rich content (`Services/FormsService.php:346-357`).
- `block.available` `pubvana.forms.form` (`Plugin.php:74-93`): options `title`, `form_id`, `form_slug`; provider renders a published form by id (preferred), falling back to slug.

**Data model.** `forms` holds name, slug, description, status (`draft`/`published`), submit label, success message, notification emails; soft-deleted via `deleted_at`. `form_fields` holds per-field `type`, `name`, `label`, `help_text`, `placeholder`, `is_required`, `width`, `options_json`, `sort_order` (renders in this order). `form_submissions` stores `status`, `ip_address`, `user_agent`, `referrer_url`, and the sanitized payload as `payload_json`.

**Render path.** `renderPublicForm()` resolves the field rows from the DB, builds the action URL from `flight.base_url` plus `routePrepend`, and renders each field by type (`text`, `email`, `phone`, `hidden`, `textarea`, `select`, `radio`, `checkbox`). Public styling ships in `assets/css/forms.css` via adext `public.css`. The submit button label and success message come from the `forms` row (`Services/FormsService.php:219-334`).

**Submission path.** The public controller loads the published form, delegates to `submitForm()` (honeypot, rate limit, captcha when the forms area is protected, per-field validation and sanitization), normalizes and redirects to `_return_url`, and stores a session flash so the next render repopulates values or errors (`Controllers/FormsPublicController.php:17-40`, `Services/FormsService.php:378-465`).

**Notifications.** If `notification_emails` lists addresses, each submission is emailed via the core mailer with a plain text/HTML body of the payload; failures are swallowed (`Services/FormsService.php:614-635`).

## Development and testing

This plugin has no `composer.json` and no test suite, unlike library plugins in the Pubvana repo. It is exercised through the full app.

- Lint/static analysis (app-wide, from the repo root; the plugin ships in-tree):
  - `vendor/bin/phpstan analyse` (level 3, sees `app/` plus `scanDirectories: vendor/`; ignored-error baseline covers the migration/activerecord internals)
  - `find plugins/Forms -name '*.php' -exec php -l {} \;`
- Manual verification checklist:
  - [ ] Create and publish a form; confirm it renders via shortcode, block, and `renderPublicForm()`, and that a draft form renders nothing
  - [ ] Submit valid data; confirm the submission stores IP/UA/referrer and the sanitized JSON payload, and the success message shows
  - [ ] Submit while the honeypot `website` field is filled; confirm no row is stored but the visitor sees success
  - [ ] Turn on the "Public forms" switch under Settings > Captcha with a configured provider and confirm the widget renders in published forms and an unverified submit is rejected with the captcha error; turn the switch off and confirm submissions pass without a token
  - [ ] Submit twice within `rate_limit_seconds`; confirm the second is rejected with the wait message
  - [ ] Add a `select`/`radio`/`checkbox` field with `options_json`; confirm a tampered option value is rejected server-side
  - [ ] Configure `notification_emails`; confirm the mailer fires and that a forced mail failure still stores the submission
  - [ ] Set `_return_url` to an external URL and confirm the redirect reverts to `/`
  - [ ] Edit a form and confirm the field set is replaced (old fields gone, order follows definitions)

No coverage is configured for this plugin. `<!-- TODO: add [coverage target] -->`

## Coding standards
- **PHPStan (level 8):** every model carries `@property`/`@method` annotations for its columns and the ActiveRecord magic it uses, and every service facade has a `@phpstan-method` entry in `phpstan-stubs.php`. Run `composer phpstan` before committing.

1. **`declare(strict_types=1);` at the top of every class file** (`Plugin.php:3`). No exceptions.
2. **Models extend `Pubvana\Models\AbstractModel` and declare their table string in the constructor** (`Models/Form.php:20-25`).
3. **Prefer the ActiveRecord fluent query.** No raw SQL exists in this plugin; keep it that way.
4. **`updateRecord()` must stay whitelisted.** Only `name`, `description`, `status`, `submit_label`, `success_message`, `notification_emails` are writable (`Models/Form.php:106`). The slug is immutable after creation, mirroring the Blog plugin convention.
5. **Escape every interpolated value in the inline HTML builder.** Field names, labels, values, options, URLs all go through `htmlspecialchars` (`Services/FormsService.php:219-334`). Never concatenate a user value raw into markup.
6. **Use `DateTimeImmutable` for all timestamp writes** (`Models/Form.php:91, 114`). Do not call `date()` for stored values.
7. **Store structured payloads as JSON with `JSON_UNESCAPED_SLASHES`** (`Services/FormsService.php:454, 523`).

## Documentation sources

| Source | Purpose |
|--------|---------|
| `README.md` | User-facing module docs: shortcode syntax, block, service API, spam controls, emails, permissions. Note: the "Exporting per form" claim at line 44 has no corresponding route or method in this plugin's code. `<!-- TODO: reconcile README export claim with code (no export handler in Plugin.php or the controllers) -->` |
| `Plugin.php:15-19` | Plugin purpose and `@package` attribution |

## Common tasks

| Goal | Where to look |
|------|---------------|
| Add an admin route | `Plugin.php:38-48` |
| Change list or submissions page sizes | `Config/Config.php` (`per_page`, `submissions_per_page`) |
| Change the spam rate limit window | `Config/Config.php` (`rate_limit_seconds`; `0` disables) |
| Add a field type | `renderPublicForm()` render branch (`Services/FormsService.php:274-328`) and `sanitizeScalarValue()` (`Services/FormsService.php:672-693`) |
| Change the shortcode syntax | `renderContentEmbeds()` + the two tokenizers (`Services/FormsService.php:346-357, 564-587`) |
| Change the default seed form | `Database/Seeds/Seed.php` (forms + form_fields rows) |
| Add a column to `forms` | Migration `2026-08-29-100001`, `Models/Form.php` property docblock, and the `updateRecord()` whitelist |

## PR / contribution checklist

- [ ] Every claim in changed code is grounded in the actual plugin code; no guessing at behavior
- [ ] `declare(strict_types=1)` present; no em dashes in new prose; one-line reasons preserved on any edited guideline
- [ ] PHP syntax verified (`php -l`) and PHPStan level 3 is clean on the app
- [ ] New write paths go through `FormsService`; field sync stays delete-then-reinsert
- [ ] Public renders/submits gate on `published`; slug immutable; escaping intact in the inline builder
- [ ] Honeypot, rate limit, per-type validation, and sanitization order preserved on any submission-path change
- [ ] `_return_url` normalized before redirect; mail failures still swallowed
- [ ] README updated only if user-facing behavior changed; doc/README claims cross-checked against code

## Out of scope / non-goals

- This is an in-tree application plugin, not a Composer package; no `composer.json` and nothing for Packagist.
- No file uploads in forms; submissions are text/JSON only.
- Spam control is the honeypot, the session rate limit, and (when switched on) captcha via the core CaptchaService; no additional spam engine of its own.
- No localization; labels and messages are hardcoded English.
- No per-field conditional logic or multi-step forms.
