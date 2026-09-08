# AGENTS.md — Core Blocks plugin

Guidance for AI agents contributing to this plugin, which ships inside the main Pubvana repo.

## Overview

Core Blocks provides two generic blocks (Text and HTML) that do not belong to any specific content plugin. Both are registered declaratively, with no PHP registration logic.

- **Package:** `pubvana/core-blocks` (`pubvana.json:2`), semver `0.1.0`, category `blocks`
- **License:** MIT, matching the main project (repo `composer.json` declares `"license": "MIT"`)
- **PHP floor:** not declared in the plugin; the main project requires PHP `^8.2` (repo `composer.json`), and the code uses only typed signatures and arrow-free closures, staying within that floor
- **Namespace:** `Pubvana\Plugins\CoreBlocks` (`Plugin.php:5`)
- **Runtime dependencies:** core Flight (`flight\Engine`, `flight\net\Router`) and `Pubvana\Services\PluginInterface`; block behavior relies on the core RegionManager and adext block registry, no third-party packages
- **Docs:** none existed; `README.md` added alongside this file

## Project guidelines

1. **Register blocks in `pubvana.json`, never in `Plugin.php`.** `Plugin::register()` must stay a no-op (`Plugin.php:13-18`). Reason: RegionManager reads `provides.block.available` and does not need a PHP callable for static blocks.
2. **Do not set a provider callable on a static block.** When no provider is set, RegionManager passes saved options directly as template data (`Plugin.php:15-17`). Reason: a provider would introduce a code path for data that already arrives in the template.
3. **Keep the template key without a `/public/` segment.** The registered key is `pubvana/core-blocks/blocks/{name}` (`pubvana.json:13, 31`), while the file lives under `Views/public/blocks/{name}.tpl`. Reason: the plugin view path mapping resolves the suffix differently than the adext block key.
4. **Keep the escaping boundary strict.** `text.tpl` renders `title` and `content` escaped (`{{ content }}`, `Views/public/blocks/text.tpl:3-7`); `html.tpl` renders `content` raw (`{! content !}`, `Views/public/blocks/html.tpl:3`). Never swap the two. Reason: the HTML block is explicitly the unescaped escape hatch for trusted admin markup; the Text block is not.
5. **Keep options schema scalar and defaulted.** Text takes `title` (input) and `content` (textarea); HTML takes only `content` (`pubvana.json:10-40`). Reason: saved options are handed to the template as-is, so every option the template reads must have a default in the schema.

## Repository layout

```
plugins/CoreBlocks/
├── Plugin.php                 No-op entry point; block registration lives in pubvana.json
├── pubvana.json               Manifest; provides.block.available (text, html)
└── Views/public/blocks/
    ├── text.tpl               Escaped title + content block
    └── html.tpl               Unescaped HTML block
```

## Core architecture

**Entry point.** `Plugin::register()` is intentionally empty (`Plugin.php:13-18`). There is no service, config, database table, controller, model, or admin page.

**Registration.** All behavior is declarative in `pubvana.json` under `provides.block.available`:

- `text` (`pubvana.json:10-27`): label Text, priority 100, options `title` (input, default `''`) and `content` (textarea, default `''`), template `pubvana/core-blocks/blocks/text`.
- `html` (`pubvana.json:28-40`): label HTML, priority 110, option `content` (textarea, default `''`), template `pubvana/core-blocks/blocks/html`.

**Data flow.** An admin places a block in a region via the core block picker. RegionManager stores the options chosen in the UI, then, because no provider callable exists, feeds those saved options directly to the Vision template as data (`title`/`content`).

**Templates.** Both `.tpl` files wrap output in `.block` wrappers. `text.tpl` conditionally prints an `<h6 class="block-title">` for `title` and escapes `content`; `html.tpl` prints `content` with Vision's unescaped `{! ... !}` operator.

## Development and testing

This plugin has no `composer.json` and no test suite. It is a declarative plugin with no runtime code paths to execute.

- Lint/static analysis (app-wide, from the repo root; the plugin ships in-tree):
  - `vendor/bin/phpstan analyse` (level 3); PHPStan does not analyze `plugins/`, so syntax is the main automated check
  - `find plugins/CoreBlocks -name '*.php' -exec php -l {} \;`
- Manual verification checklist:
  - [ ] Drop a Text block into a region: options render raw text and HTML entities properly escaped
  - [ ] Drop an HTML block into a region: markup renders unescaped and unstyled beyond `.block-html`
  - [ ] The block appears in the block picker under both registered labels, in priority order
  - [ ] `Plugin.php` remains a no-op with no service, routes, or assets added

No coverage is configured for this plugin. `<!-- TODO: add [coverage target] -->`

## Coding standards
- **PHPStan (level 8):** every model carries `@property`/`@method` annotations for its columns and the ActiveRecord magic it uses, and every service facade has a `@phpstan-method` entry in `phpstan-stubs.php`. Run `composer phpstan` before committing.

1. **`declare(strict_types=1);` at the top of the class file** (`Plugin.php:3`).
2. **Prefer declarative registration over PHP registration.** Static blocks with static options belong in `pubvana.json`, not in `register()`.
3. **Templates stay output-only.** No logic beyond the Vision tags already used (`{% if %}`, `{{ }}`, `{! !}`); keep the escaping boundary from the guidelines.

## Documentation sources

| Source | Purpose |
|--------|---------|
| `README.md` | User-facing docs: features, installation, usage, contributing |
| `pubvana.json` | Single source of truth for block keys, labels, options, and priorities |

## Common tasks

| Goal | Where to look |
|------|---------------|
| Add a new generic block | New entry under `provides.block.available` in `pubvana.json` plus a `.tpl` under `Views/public/blocks/` |
| Change block labels, priorities, or option schema | `pubvana.json` (block entries) |
| Change block markup | `Views/public/blocks/{text,html}.tpl` |

## PR / contribution checklist

- [ ] Every claim in changed code is grounded in the actual plugin code; no guessing at behavior
- [ ] `declare(strict_types=1)` present where PHP is edited; no em dashes in new prose; one-line reasons preserved on any edited guideline
- [ ] `php -l` passes on the class file; template keys, option defaults, and template data names match exactly
- [ ] New blocks are registered in `pubvana.json` with a `priority` and option defaults, and the matching `.tpl` under `Views/public/blocks/`
- [ ] Escaping boundary preserved: no escaping change without a deliberate reason, documented in the guideline
- [ ] `Plugin::register()` remains a no-op; README updated only if user-facing behavior changed

## Out of scope / non-goals

- This is an in-tree application plugin, not a Composer package; no `composer.json` and nothing for Packagist.
- No configuration: no `Config/` file, no settings, no environment variables.
- No database tables or migrations, no admin UI beyond the shared block picker.
- No providers, services, controllers, or routes; blocks are purely presentational.
