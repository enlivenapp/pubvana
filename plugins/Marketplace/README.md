# Pubvana Marketplace

The companion app for the Pubvana Digital Store at pubvanacms.com. Browse the catalog, push items to your account-bound cart, purchase on the pubvanacms.com checkout, then verify and install what you bought on this site.

Package: `pubvana/marketplace`. It is a separate plugin from the Digital Store and never merges with it.

## What it does

1. **Connect a Pubvana account.** Under Tools > Marketplace, sign in with your admin email and a password for the pubvanacms.com store account (email plus password are sent together). If no account exists for that email, the store creates one and you activate it by email before connecting. Your admin email is used automatically, so each admin can connect their own store account. The connect screen discloses that this site phones home to pubvanacms.com about every two weeks to verify purchases and licenses.
2. **Browse the catalog.** The store's marketplace-listed products are fetched over a server-to-server API (no CORS) and rendered here. Add any item to your account-bound cart.
3. **Purchase on the Pubvana website.** "Purchase on pubvanacms.com" opens the store checkout in a new tab with the cart already loaded. No payment happens on this site.
4. **Purchases.** "Purchases" verifies which owned products license this domain and lists each one with its license state (valid, scope, expiry/renewal), plus whether it is installed and its version.
5. **Install / reinstall.** Install a purchased plugin or theme directly to this site. Reinstall-all reinstalls every owned, licensed item at once.
6. **Domain move.** A single-site license bound to another domain triggers the domain transfer flow: the store emails a confirmation link, and installing resumes after the transfer is confirmed.

## Licensing model

- Products carry `license_scope` of `single_site`, `multi_site`, or `none`.
- Licenses are domain-bound at purchase and verified back at the store; the Marketplace never asks the buyer to type a key (though keys exist for admin and diagnostics).
- The internal `marketplace_installs` table is bookkeeping only and is not surfaced as a key list to users.

## Configuration

`Config/Config.php`:

| Key | Default | Meaning |
|-----|---------|---------|
| `routePrepend` | `marketplace` | URL prefix |
| `store_url` | `https://pubvanacms.com/store` | Where the Digital Store API lives |
| `api_timeout` | `10` | HTTP timeout seconds |
| `catalog_cache_ttl` | `3600` | Catalog fetch cache TTL |
| `verify_days` | `14` | Phone-home cadence in days |
| `revalidate_days` | `90` | Legacy re-validate ceiling (unused currently) |

## Cron

Registers a `24h` cron task that calls `verifyIfDue()`. The task itself enforces `verify_days`, so the store is contacted about every 14 days rather than daily. Real failures throw so `cron` logs FAILED; a quiet "not due" returns normally.

## Permissions

- `marketplace.manage` (seeded) gates the entire Tools > Marketplace admin surface.

## Technical notes

- All HTTP calls use curl with a `file_get_contents` fallback, a timeouts, and a non-empty user agent (`Pubvana-Marketplace/3.0`).
- Package downloads only accept hosts under pubvanacms.com/pubvana.net; folder names are validated; zip entries are inspected for path traversal before extraction (same safety contract as the Updates plugin).
- The account token lives in the settings store under `Marketplace.*` keys.

## Development

```bash
php -l plugins/Marketplace/**/*.php
composer phpstan
composer psalm
vendor/bin/phpunit
```

## Files at a glance

```
Marketplace/
├── pubvana.json                     Manifest: Tools > Marketplace menu
├── Plugin.php                       Registers facade, admin routes, cron task
├── Config/Config.php                Defaults (table above)
├── Controllers/MarketplaceAdminController.php
├── Services/MarketplaceService.php  Store API client, install, verify, transfer
├── Models/MarketplaceInstall.php    Marketplace-only install records
├── Database/Migrations/2026-09-06-000001_CreateMarketplaceInstallsTable.php
├── Database/Seeds/Seed.php          marketplace.manage permission
└── Views/admin/                     index.php (connect + catalog), purchases.php
```