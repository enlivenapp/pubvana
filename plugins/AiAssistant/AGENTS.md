# AGENTS.md — AI Assistant plugin

Guidance for AI agents contributing to this plugin, which ships inside the main Pubvana repo.

## Overview

**pubvana/ai** (display name "AI Assistant") is a folder of API-key ingestion endpoints and per-key grants that let an external AI assistant read and write site content over a sessionless REST API, plus a site-level Fact Checking feature.

- **Package:** `pubvana/ai` (local plugin, no Packagist)
- **License:** MIT, matching the main project (repo `composer.json` declares `"license": "MIT"`)
- **PHP:** not declared in the plugin; the main project requires PHP `^8.2` (repo `composer.json`), and the code stays within that floor (`match`, `mixed`, `str_contains`)
- **Namespace:** `Pubvana\Plugins\AiAssistant` (PSR-4 style, matches the folder path)
- **Runtime dependencies:** pulled from the app's composer.json, not the plugin: `league/commonmark`, `league/html-to-markdown`, `ezyang/htmlpurifier`
- **Peer plugin dependencies:** Blog, Pages, Comments, Redirects, Navigation, SEO, and the Settings service. Every `svc()` call depends on the peer plugin being registered
- **Manifest:** `pubvana.json` (admin menu under Tools: Manage, Fact Checking, Help)
- **Config:** `Config/Config.php` (also `Config/fact-check-prompt.json`, the bundled fact-checking prompt)
- **Docs:** [README.md](./README.md), [AI-README.md](./AI-README.md) (the API guide written for the AI itself)

## Project guidelines

1. **Never weaken the key security model.** Only an HMAC-SHA256 hash of a token is stored (`AiService.php:827`), keyed by a domain key derived from `SESSION_ENCRYPTION_KEY` (`AiService.php:835`). The plaintext token is revealed exactly once at creation (`AiAdminController.php:60`). Never log, store, or cache a plaintext token.
2. **Grants are deny-all.** A key with no grants can authenticate but nothing else. Every grant decision flows through `helpCatalog()` as the single source of truth (`AiService.php:321`) and `requireGrant()` as the hard gate (`AiApiController.php:821`). Do not add an ungated endpoint or a "grant everything" escape hatch.
3. **Fact checking is site-level, and the toggle is the grant.** Fact-check endpoints open to every authenticated key when the admin has accepted the current prompt's terms and switched the service on (`FactCheckService::gateStatus()`), and refuse everything otherwise. They appear in no per-key grant form and in no `helpCatalog()` row. The prompt endpoint (`GET /api/ai/fact-check/prompt`) is the one exception: any authenticated key may read the terms even while the service is off. Submissions must attest to the current prompt version (`409` otherwise), and post/page submissions additionally require the matching `posts.read`/`pages.read` grant.
4. **Every request goes through the audit log.** `AiService::log()` records ok/denied/error outcomes including unauthenticated attempts (`AiService.php:239`). New endpoints must log with the same shape. Logging is tolerant by design: a missing table must not break the request (`AiService.php:256`).
5. **Keep the response envelope.** All public API responses are `{status, data, errors}` via `ok()` and `fail()` (`AiApiController.php:844`, `AiApiController.php:853`). Do not return a different shape from a new endpoint.
6. **Reuse peer plugin services instead of writing SQL.** Content operations go through `$this->svc('blog')`, `svc('pages')`, `svc('comments')`, `svc('redirects')`, `svc('navigation')`. When a peer plugin is unavailable the request fails with 503 (`AiApiController.php:834`). Direct DB work belongs only in this plugin's own models (`AiKey`, `AiKeyGrant`, `AiLog`, `AiFactCheck`).
7. **Path-order matters in route registration.** Static taxonomy routes (`/api/ai/posts/tags`, `/api/ai/posts/categories`) must stay registered before the parameterized `/api/ai/posts/@slug` route (`Plugin.php:68`). Flight matches in order; moving the static routes below the parameterized ones breaks them.

## Repository layout

```
AiAssistant/
  Plugin.php                    # Entry point: maps 'ai', 'aiFactCheck', and 'aiMarkdown' services; registers admin + public routes, editor panel, block
  pubvana.json                  # Plugin manifest and admin menu under Tools (Manage, Fact Checking, Help)
  README.md                     # Short human-facing intro and where-to-go-next
  AI-README.md                  # Full API guide written for the AI caller
  Config/
    Config.php                  # Defaults: key_prefix, max_failed_attempts, block_minutes, log_limit, factcheck URLs/timeout
    fact-check-prompt.json      # Bundled copy of the fact-checking prompt (fallback when the hosted fetch fails)
  Controllers/
    AiAdminController.php       # Admin: manage, createKey, updateGrants, toggleKey, deleteKey, saveAuthor, help
    AiFactCheckAdminController.php # Admin: fact-checks index/show, acceptTerms, toggle, delete
    AiApiController.php         # Sessionless REST: /api/ai/* endpoints, auth, grants, audit logging, helpers
  Services/
    AiService.php               # Keys, grants, auth, audit log, help catalog, content serializers
    FactCheckService.php        # Fact-checking gate, prompt fetch, report validation/storage, staleness, panel + block data
    MarkdownService.php         # Markdown -> sanitized HTML and HTML -> Markdown
  Models/
    AiKey.php                   # ai_keys table model
    AiKeyGrant.php              # ai_key_grants table model
    AiLog.php                   # ai_logs table model
    AiFactCheck.php             # ai_fact_checks table model
  Database/
    Migrations/                 # Creates ai_keys, ai_key_grants, ai_logs, ai_fact_checks
    Seeds/Seed.php              # Seeds the 'ai.manage' permission
  Views/
    admin/manage.php            # Keys, grants, default author, audit log
    admin/fact-checks.php       # Terms acceptance, toggle, report history
    admin/fact-check-detail.php # One full report
    admin/fact-check-panel.php  # Read-only panel in post/page editors (content.edit.panel)
    admin/help.php              # Admin-facing help and endpoint reference
    public/blocks/fact-check-summary.tpl # Public block (Vision)
```

## Core architecture

### Plugin registration

`Plugin.php:31` maps three singletons on the app engine: `ai` (an `AiService` wired to `$app->db()`, the engine, and the plugin config), `aiFactCheck` (a `FactCheckService` with the same wiring), and `aiMarkdown` (a `MarkdownService` with the plugin config). Admin routes are registered under `pubvana.ai` and gated by a `PermissionMiddleware` for the seeded `ai.manage` permission (`Plugin.php:53`). Public REST routes hang off `routePrefix('pubvana/ai')` so the URL prefix is configurable (`Plugin.php:50`).

The CSRF middleware skips `/api/ai/*` (noted at `Plugin.php:23`), because these endpoints carry no session; auth is per-request bearer keys instead.

### Authentication and grants

`AiService::authenticate()` (`AiService.php:190`) hashes the bearer token, looks it up by hash, rejects disabled and blocked keys, and resets failure state on success. Disabled-key probing counts toward a block: after `max_failed_attempts` failures the key is blocked for `block_minutes` (`AiService.php:850`). Every successful call stamps `last_used_at`.

Each endpoint calls `requireKey()` (`AiApiController.php:793`) for auth and `requireGrant()` (`AiApiController.php:821`) for the specific permission. A held permission is checked against the per-request cached grant set built from `AiKeyGrant::permissionsFor()` (`AiService.php:222`).

### Content flows

Content operations delegate to peer plugins:

- Posts: `svc('blog')` create/update/delete, tags, and categories
- Pages: `svc('pages')` create/update/delete
- Comments: `svc('comments')` list/approve/reject/delete
- Redirects: `svc('redirects')` create/update/delete
- Navigation: `svc('navigation')` create/delete, plus a direct `NavigationItem` model update because NavigationService has no update method (`AiService.php:611`)

Markdown is converted to sanitized HTML at ingest via `MarkdownService::toHtml()` and back to Markdown for reads via `toMarkdown()` (`AiApiController.php:912`, `AiService.php:588`). AI-created posts and pages are attributed to the configured default author (stored as the `Ai.default_author_id` setting, `AiService.php:297`). An optional nested `seo` block is persisted through the SEO plugin when present (`AiApiController.php:937`).

### Fact checking

The checking brain is external (the site owner's AI assistant over the API); the plugin owns everything around it (`FactCheckService`):

- **Prompt.** The versioned terms/instructions are fetched from `factcheck_prompt_url` with the bundled `Config/fact-check-prompt.json` as fallback; the result is hashed and cached per request (`currentPrompt()`). The AI must re-fetch it before every check and attest `prompt_version` on submission.
- **Gate.** `Ai.factcheck_enabled`, `Ai.factcheck_terms_version`, `Ai.factcheck_terms_accepted_at` settings drive `gateStatus()`: off is `403`, terms-version mismatch is `409`. Enabling requires terms acceptance plus at least one enabled key (`enableBlockers()`).
- **Reports.** `submitReport()` validates the payload (verdicts `supported|partially_supported|refuted|unverifiable`, claim kinds `fact|opinion`, bounded lengths, capped counts), snapshots content identity (`content_title`, `content_slug`, `content_updated_at`) and key identity, and appends to `ai_fact_checks`. Reports are a history: resubmits add rows, nothing is replaced.
- **Staleness.** `isStale()` compares the snapshot against the live content; stale reports are badged, never deleted.
- **Surfaces.** Read-only editor panel via `content.edit.panel` (`fact-check-panel.php`), the admin history/detail pages, and the `fact-check-summary` block whose provider detects the current post/page from the URL the way SEO's `detectContent()` does, rendering nothing on anything else.

### Audit log

`AiService::log()` writes one row per API request to `ai_logs`, snapshotting the key name so the trail survives key deletion (`AiService.php:239`). Failures to write are swallowed and pushed to `error_log`.

## Development and testing

This plugin has a unit test suite for the fact-checking service; the rest is exercised through the full app.

```bash
php -l plugins/AiAssistant/Plugin.php           # lint every touched file
vendor/bin/phpunit tests/Unit/Plugins/AiAssistant/FactCheckServiceTest.php
```

- Verify the admin screens at `/admin/ai/manage`, `/admin/ai/fact-checks`, and `/admin/ai/help` after any controller or view change.
- Generate a key end to end, confirm the plaintext shows once, then disable/delete it from the admin row actions.
- Exercise the public API with a bearer token and confirm 401 (no key), 403 (no grant), 422 (bad input), and the `{status, data, errors}` envelope.
- Confirm a request against a disabled key clicks up `failed_attempts` and blocks when the threshold is crossed, and that a sessionless request still works (no CSRF token involved).
- Fact checking end to end: accept terms, toggle on, `GET /api/ai/fact-check/prompt`, submit a report, see it in the history and editor panel, edit the content and confirm the stale badge, toggle off and confirm endpoints refuse.
- Coverage: `tests/Unit/Plugins/AiAssistant/FactCheckServiceTest.php` covers the service; controllers and views stay manual. `<!-- TODO: add [coverage target] -->`

## Coding standards
- **PHPStan (level 8):** every model carries `@property`/`@method` annotations for its columns and the ActiveRecord magic it uses, and every service facade has a `@phpstan-method` entry in `phpstan-stubs.php`. Run `composer phpstan` before committing.

Steps that go beyond the repo-wide style, derived from the existing code:

1. `declare(strict_types=1);` first line in every class file.
2. Class name, file name, and namespace must align: `Pubvana\Plugins\AiAssistant\Services\AiService` lives in `Services/AiService.php`.
3. Endpoints keep the sequence: authenticate, require grant, validate input, act, log, respond through `ok()`/`fail()`. On validation failure, log a specific `error` detail before `fail()`.
4. Every new permission must be added to `helpCatalog()` (`AiService.php:321`) with its route group, label, summary, and endpoints. The catalog drives `/api/ai/help`, the admin help page, and grant-form rendering, so it is the point of truth for grants.
5. All API reads return display-safe arrays; HTML content is served as Markdown and never raw. Serializers (`serializePost`, `serializePage`, `serializeComment`, `serializeRedirect`, `serializeNavigationItem`) must stay in `AiService`.
6. Keep pagination bounded: `per_page` is clamped to `[1, 100]` and `page` to `>= 1` for every list endpoint (`AiApiController.php:106`). Do not introduce an unbounded list.
7. Grant-check before acting: posting a `published` status requires the `publish` grant, not the bare create/update grant. Do not publish or schedule under the write grant alone.
8. Use the tolerant `svc()` wrapper for peer services and the tolerant `seoBlock()`/`saveSeo()` for optional SEO, so missing peer plugins degrade to a 503 or a no-op instead of a hard crash.
9. Do not hard-code the `/ai` URL prefix; use `$this->path()` and `routePrefix('pubvana/ai')` the way the existing code does.

## Documentation sources

| Resource | Use for |
|----------|---------|
| [AI-README.md](./AI-README.md) | The complete endpoint reference, grant list, and request/response examples for the AI caller |
| [README.md](./README.md) | Human-facing intro and pointing reader to the AI guide and live `/api/ai/help` |
| [help.php](./Views/admin/help.php) | Admin-facing plain-language description of each grant |
| [helpCatalog()](./Services/AiService.php) | Single source of truth for grant semantics |

## Common tasks

| Goal | Where to look |
|------|---------------|
| Add a permission (and its endpoint) | `helpCatalog()` at `AiService.php:321`, then the API methods in `AiApiController.php`, then a route in `Plugin.php:71` |
| Change the fact-checking gate, prompt fetch, report rules, or staleness | `FactCheckService.php` |
| Change the fact-check prompt text or version | `Config/fact-check-prompt.json` (bundled copy) and the hosted source at `factcheck_prompt_url` |
| Change the public fact-check block | provider data in `FactCheckService::blockData()`, template `Views/public/blocks/fact-check-summary.tpl`, registration in `Plugin.php:144` |
| Change the editor fact-check panel | `FactCheckService::panelData()` + `Views/admin/fact-check-panel.php` |
| Change key tuning (prefix, block threshold, block minutes, log limit) | `Config/Config.php:5` |
| Change the audit log shape | `AiService::log()` at `AiService.php:239`, `AiLog.php`, and the manage view at `Views/admin/manage.php:251` |
| Change markdown sanitation options | `MarkdownService.php:31` (commonmark options) |
| Add or change a serializer | `AiService.php:672` and below |
| Change the default-author behavior | `AiService::defaultAuthorId()` at `AiService.php:297` + `AiAdminController::saveAuthor()` at `AiAdminController.php:117` |
| Change the admin grant form | `Views/admin/manage.php:203` |
| Extend the admin help screen | `Views/admin/help.php` |

## PR / contribution checklist

- [ ] Changes fit the project guidelines (no weakened key logic, no ungated endpoint, grants stay deny-all, fact-check gate stays site-level)
- [ ] `php -l` clean on every touched file
- [ ] New permission/endpoint documented in `helpCatalog()`, AI-README.md, and the admin help view as appropriate (fact-check endpoints: AI-README.md and the admin help view only)
- [ ] Endpoint verified for 401, 403, 422, and the `{status, data, errors}` envelope
- [ ] Audit log written and tolerated-failure path intact
- [ ] Static routes still registered before parameterized routes in `Plugin.php`
- [ ] No plaintext or hashed API key values committed anywhere
- [ ] README.md updated if user-facing behavior changed

## Out of scope / non-goals

- A replacement for the admin content editors. This plugin exposes a machine-facing API for an external assistant; admin UI is thin and stays thin.
- Chat or prompt UI, model integrations, or client-side SDKs. The plugin only exposes the REST API and its admin management screens; fact checking is no exception, the checking brain stays external.
- Replacing the peer plugins' write services (Blog, Pages, Comments, Redirects, Navigation). It calls them and serializes their results.
- `brokenLinks` and `analytics` are stubs and return 501 until a real implementation lands (`AiApiController.php:718`).
- The hosted fact-check prompt itself (`pubvanacms.com/fact-checking/prompt.json`) is served outside this repo; the bundled JSON is only a fallback.
