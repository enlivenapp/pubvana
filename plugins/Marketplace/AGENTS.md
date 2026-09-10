# AGENTS.md — Marketplace plugin

Guidance for AI agents contributing to this plugin, the buy-side companion for the Pubvana Digital Store.

## Overview

**pubvana/marketplace** is the companion app for the store at pubvanacms.com. It never processes payments and never merges with the Digital Store plugin. The Marketplace browses the store catalog, pushes items to an account-bound cart, opens the store checkout in a new tab, then verifies and installs purchases on this site.

- **Package:** `pubvana/marketplace` (`pubvana.json:2`), semver `0.1.0`, category `commerce`
- **License:** MIT, matching the main project
- **PHP floor:** the main project requires PHP `^8.2`; code stays within it
- **Namespace:** `Pubvana\Plugins\Marketplace` with `Controllers`, `Services`, `Models`, and `Database\Seeds` sub-namespaces
- **Runtime dependencies:** Pubvana core (Engine, AdminController, PluginInterface, adext, settings, shield, sessions, CSRF), `enlivenapp/migrations`, curl with `file_get_contents` fallback
- **One database table:** `marketplace_installs`. It is internal bookkeeping only. No user-facing key list is rendered from it.
- **Config:** `Config/Config.php`: `routePrepend`, `store_url`, `api_timeout`, `catalog_cache_ttl`, `verify_days` (default 14), `revalidate_days`
- **Docs:** [README.md](./README.md)

## Project guidelines

1. **Two-plugin boundary is sacred.** Marketplace is a separate plugin from the Digital Store. Do not merge them, do not call into store models directly across the plugin boundary, and do not change store plugin files from here. The Marketplace talks to the store only over its HTTP API.
2. **No user-facing key entry.** The Marketplace never prompts the buyer to type a license key. Ownership is verified back at the store with the account token. `license_key` is stored in `marketplace_installs` for diagnostics only.
3. **Phone-home cadence is `verify_days`, not daily.** The 24h cron task calls `verifyIfDue()`, which enforces the cadence itself. Do not hit the store on every request or every cron tick. The connect screen must disclose periodic verification (~2 weeks).
4. **Validate every zip before extraction.** Reject entries with `..`, absolute paths, drive letters, or NUL bytes; only accept download hosts under pubvanacms.com/pubvana.net. This is the same safety contract as the Updates plugin; do not weaken it.
5. **No payment integration here.** Checkout happens on pubvanacms.com. The Marketplace only opens the store checkout URL and verifies afterward.
6. **Single-site domain moves require email confirmation.** The transfer-request endpoint starts the flow; the store emails a confirm link. The Marketplace can prompt and request; it must not rebind on its own.
7. **Controllers strip `_csrf_token` before POST data use; permission gate is flash + redirect, never `halt()`.** Standard v3 patterns.
8. **HTTP always:** curl first, `file_get_contents` fallback, timeouts, non-empty user agent.

## Core architecture

### Store API surface (server-to-server, no CORS)

- `GET {store}/api/store/categories` - categories
- `GET {store}/api/store/items?currency=` - marketplace-listed items
- `POST {store}/api/store/cart/add` - push item into account-bound cart
- `GET {store}/api/store/purchases?domain=` - owned products + license state for this domain
- `POST {store}/api/store/license/validate` - returns a download URL for a valid license
- `POST {store}/api/store/license/transfer-request` - begin a domain move
- `POST {store}/api/store/auth/token` - exchange email for account token

Auth is a `Marketplace.account_token` setting sent as a `Bearer` header (and echoed in the query for GETs).

### Install flow

`install($storeProductId, $itemType)` loads the local install record, validates the license via the API for this domain, downloads the package (host-checked), inspects the zip, extracts under `writable/cache/marketplace`, moves the resolved root (single-wrapper-dir aware) into `plugins/{folder}/` or `themes/{folder}/`, then records the installed version.

`reinstallAll()` iterates purchases and reinstalls every licensed, non-`file` item already installed locally, reporting ok/skipped/failed counts.

`verifyIfDue()` / `purchases()` reconcile `marketplace_installs` from the store's answer for this domain, updating license validity, scope, expiry/renewal, and registered domain. `localInstallRecords()` serializes the table for the Purchases view.

### Connect / account

`connectAccount($email)` exchanges the email for an account token, stores `Marketplace.account_token`, `account_email`, `connected_at`, and `verify_disclosed_at`. `disconnectAccount()` blanks the token and email.

## Development and testing

```bash
php -l <touched files>                 # lint
composer phpstan                       # level 8, via composer
composer psalm                         # taint analysis
vendor/bin/phpunit                     # run the suite
php runway cron 24h                    # exercise the cron task (graceful when not due)
```

## Coding standards

- **PHPStan (level 8):** the `marketplace()` facade has an `@phpstan-method` entry and a `MarketplaceService` shell in `phpstan-stubs.php`.
- `declare(strict_types=1);` first line in every class file.
- Docblock header on every file: `@package Pubvana\Plugins\Marketplace`.
- Namespace matches directory exactly.
- All HTTP uses curl first, `file_get_contents` fallback, both timeouts, non-empty user agent.
- Views escape everything with `htmlspecialchars`; admin URLs use the `/admin/marketplace/...` prefix (adext prepends `/admin` automatically for admin routes).
- New settings keys use the `Marketplace.` namespace through the settings store, not the config file.

## PR / contribution checklist

- [ ] `php -l` clean on every touched file; `composer phpstan` and `composer psalm` clean
- [ ] `vendor/bin/phpunit` green
- [ ] Zip safety intact (traversal entries still rejected; host allow-list unchanged)
- [ ] Phone-home still cadence-gated (no per-request store hits)
- [ ] README updated if user-facing behavior or config changed

## Out of scope

- Payments, refunds, or gateway config (store's territory).
- The Digital Store plugin itself: don't modify it from here.
- License issuance and key generation (store-side).
- Domain transfer confirmation logic (store-side; Marketplace only requests).