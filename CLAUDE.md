# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Stripe Express Checkout by Country is a small WordPress/WooCommerce plugin that
makes the **Stripe Express Checkout** (Apple Pay / Google Pay / the Express
Checkout Element) conditional on the customer's country. It works alongside the
official **WooCommerce Stripe Gateway** plugin, which by default offers express
checkout to everyone, everywhere.

The store operator configures a list of countries and a mode:

- **Allow-list** — "Only show express checkout for these countries"
- **Block-list** — "Show express checkout for all customers, except these countries"

- **Namespace:** `Woo_Stripe_Express_By_Country`
- **Text Domain:** `woo-stripe-express-by-country`
- **PHP:** 8.0+ (do NOT use `declare(strict_types=1)` — breaks WordPress/WooCommerce interop)
- **WordPress:** 6.0+, WooCommerce recent (declares HPOS compatibility)
- **No build system** — no npm, no Composer, no bundler. Plain PHP/CSS/JS.
- **Depends on:** WooCommerce Stripe Gateway (provides the `wc_stripe_*` display filters).

## Commands

```bash
phpcs                  # Check WordPress coding standards compliance
phpcbf                 # Auto-fix coding standards violations
phpcs includes/        # Check a specific directory
```

Always run `phpcs` before committing. The config is in `phpcs.xml` — WordPress
standards with prefixes: `woo_stripe_express_by_country`, `wsec`, `WSEC`,
`Woo_Stripe_Express_By_Country`.

## Architecture

### Entry Point & Bootstrap

`woo-stripe-express-by-country.php` is the main plugin file. It is **not
namespaced** — it defines the `WSEC_*` path/URL/version globals with global
`const` / `define()`, requires `constants.php`, `functions-private.php`, and the
class files, declares HPOS compatibility, and launches the plugin via the
fully-qualified `Woo_Stripe_Express_By_Country\Plugin`. Everything under
`includes/` and `constants.php` / `functions-private.php` IS namespaced and
references the `WSEC_*` globals by their unqualified names (PHP falls back to the
global constant). The launched instance is stored in the global `$wsec_plugin`.

### Core Classes

- **`Plugin`** (`includes/class-plugin.php`) — Orchestrator. **All hooks are
  registered here**, in `run()` (early hooks) and the `init()` / `admin_init()` /
  `admin_menu()` WP action handlers it registers. `$settings` is instantiated
  eagerly in `run()`; other components are lazy via `get_*()` getters (each
  property is declared directly above its getter). Component handler methods live
  on the components but are pointed at from `Plugin`.
- **`Settings`** (`includes/class-settings.php`) — Admin submenu page (under the
  WooCommerce menu, `manage_woocommerce`), save handler with nonce + capability
  checks and sanitisation, and type-safe getters `get_mode()` / `get_countries()`.
- **`Restrictions`** (`includes/class-restrictions.php`) — The enforcement logic:
  the shared country-decision helpers plus both enforcement layers (below). It
  reads its configuration through the `get_settings_controller()` helper, **not**
  constructor injection.
- **`Github_Updater`** (`includes/class-github-updater.php`) — Self-updates from
  GitHub Releases. Instantiated in `Plugin::run()`; registers its own hooks into
  the WP plugin-update system. Config via the `UPDATER_*` constants; toggle with
  the `wsec_updater_enabled` filter. Paired with `.github/workflows/release.yml`
  (builds the release zips on a `v*.*.*` tag) and `.distignore` (what to exclude
  from the shipped zip). Adapted from the shared Headwall updater — keep changes
  minimal so it stays in sync across plugins.

### Global Accessors

`functions-private.php` holds namespaced accessor functions so hook handlers can
reach the plugin and its components without constructor injection:

- `get_plugin(): Plugin` — returns the global `$wsec_plugin` instance.
- `get_settings_controller(): Settings` — returns the settings controller. Named
  this way (not `get_settings()`) to avoid colliding with WordPress core's
  deprecated `get_settings()` function.

### Two Enforcement Layers

1. **Display gate** — hides the wallet buttons from disallowed customers via the
   Stripe gateway's per-location filters:
   - `wc_stripe_show_payment_request_on_cart`
   - `wc_stripe_show_payment_request_on_checkout`
   - `wc_stripe_hide_payment_request_on_product_page` (inverted: `true` = hide)

2. **Server-side hard guard (optional; deferred, currently OFF)** — rejects the
   order at checkout if it was placed via express checkout from a disallowed
   country, even if the customer resolved a different address inside the wallet
   sheet. The whole feature is gated behind the `IS_SERVER_HARDBLOCK_AVAILABLE`
   constant in `constants.php` (currently `false`) — while false, the setting is
   hidden, not saved, and the guards are never registered, so the plugin ships
   display-gate-only. When available, it is further gated behind the
   `OPT_HARD_BLOCK` setting and only registered in `Plugin::run()` when enabled.
   The display gate is the primary mechanism; this is opt-in because the
   country signals (IP geo / typed address / wallet address) routinely disagree,
   so an always-on block can reject a wallet payment mid-flow. Covers both flows:
   - Classic checkout — `woocommerce_after_checkout_validation`
   - Block / Store API checkout — `woocommerce_rest_checkout_process_payment_with_context`
     (priority 50: after the gateway records express meta, before it charges;
     throws `RouteException`, with a generic `Exception` fallback).

### Country Resolution

- Basis is **shipping country, falling back to billing** when shipping is empty.
- Read via `WC()->customer` so WooCommerce supplies the value from its own
  precedence chain, including its built-in **geolocation (geo-IP) fallback** when
  the store is configured for it. We do NOT implement our own geo lookup.
- Basis is an internal constant, not a UI setting.
- **Fail closed:** an unknown/empty country is treated as *not* express-eligible
  in allow-list mode, so disallowed customers can never be exposed to, or complete
  via, express checkout.

### Mode Logic

`is_country_allowed( $country )` is the single source of truth, shared by both
layers:

- **Allow-list mode:** allowed when the country is IN the configured list.
- **Block-list mode:** allowed when the country is NOT in the configured list.
- Empty allow-list → express hidden from everyone; empty block-list → express
  shown to everyone.

### Constants

All magic strings, option keys, mode values, and the page slug live in
`constants.php` under the `Woo_Stripe_Express_By_Country` namespace. Convention:
`OPT_` for WordPress options, `DEF_` for defaults, `MODE_` for the mode values.

## Key Conventions

- Register all hooks from `Plugin` — in `run()`, or the `init()` / `admin_init()`
  / `admin_menu()` handlers it registers — and implement the behaviour in the
  respective component classes. Keeping registration in one place makes the
  bootstrap easy to trace and troubleshoot.
- Components reach configuration through the `get_settings_controller()` accessor
  in `functions-private.php`, not constructor injection.
- Use constants from `constants.php` — never hardcode option names or magic values.
- Both enforcement layers must call the same decision helpers so display and
  server-side behaviour can never diverge.
- Read customer/order country via WooCommerce APIs (`WC()->customer`, `WC_Order`
  methods) — never raw `get_post_meta()` (HPOS-compatible).
- Security: nonce verification, `manage_woocommerce` capability check, input
  sanitisation (validate country codes against `WC()->countries`, mode against
  the allowed values), output escaping on the settings page.
- Guard defensively when the Stripe plugin / Store API classes are absent so the
  plugin can never fatal a checkout request.

## Commit Messages

```
type: brief description

- Detail 1
- Detail 2
```

Types: `feat:` `fix:` `refactor:` `chore:` `docs:` `style:` `test:`

## Extensibility

The plugin exposes one filter, `wsec_is_customer_allowed` (in
`Restrictions::is_customer_allowed()`), which filters the final allow/deny
decision — `($allowed, $country)`. It drives the display gate and, when enabled,
the server-side block. See `docs/hooks.md`.

## Reference Files

- `README.md`, `docs/settings.md`, `docs/hooks.md` — user- and developer-facing docs.
- `dev-notes/00-project-tracker.md` — Milestones, decisions, and roadmap.
- Prototype (US-only theme snippet this productionises):
  `acth.headwall.tech/web/wp-content/themes/activehands/express-checkout-restrictions.php`
- House style reference: the `bullfix-erp` plugin (WPCS, constants, class
  structure, settings pattern).

<!-- wp-translate:begin v=1.1.0 hash=691a760379b54e84e600f7c601f74b3fefd832e2a31f006e2af90c7629c12f11 -->
## Translating this plugin (wp-translate conventions)

This plugin's `.po`/`.mo` files are generated from source by
[wp-translate](https://github.com/headwalluk/wp-translate-tool), which
machine-translates strings with DeepL. Machine translation is only as good as
the strings you give it — follow these conventions when adding or editing
user-facing text.

### 1. Disambiguate short or ambiguous strings with `_x()`

DeepL handles full sentences well but guesses badly on short, context-free
labels. Give it context with `_x()` (or `esc_html_x()`, `_ex()`):

```php
// Ambiguous out of context — DeepL may read "Sent" as "late", "Folder" as "leaflet"
__( 'Sent', 'woo-stripe-express-by-country' );

// Disambiguated — the context is passed to the translator and to DeepL
_x( 'Sent', 'email delivery status', 'woo-stripe-express-by-country' );
_x( 'Folder', 'IMAP mailbox', 'woo-stripe-express-by-country' );
_x( 'Open', 'verb; button label', 'woo-stripe-express-by-country' );
```

The context (2nd argument) is never shown to users. Use it whenever a string is a
single word, a short label, or has more than one plausible meaning.

### 2. Use placeholders, never concatenation

Build dynamic text with `printf`/`sprintf` so the whole sentence translates as a
unit, and add a `translators:` comment to explain each placeholder:

```php
/* translators: %s is the user's display name */
printf( esc_html__( 'Welcome back, %s', 'woo-stripe-express-by-country' ), $name );
```

Never split a sentence across multiple translation calls — word order differs
between languages.

### 3. Acronyms and technical tokens

wp-translate keeps common acronyms (`TLS`, `API`, `SMTP`, `URL`, `ID`, `UTC`, …)
verbatim automatically. If you introduce an unusual acronym or product name that
must not be translated, keep it as its own standalone string so it is recognised,
or ask the maintainer to add it to the tool's acronym list.

### 4. Don't translate dates — let WordPress localise them

Never add month or day-of-week names (full or abbreviated) as translatable
strings. DeepL frequently mistranslates short forms like `Mon`, `Tue`, `Jan`,
`Feb` even with context hints. WordPress already ships locale-aware names — use
`$wp_locale`:

```php
global $wp_locale;
$wp_locale->get_month( $month_number );        // "January" (1-based)
$wp_locale->get_month_abbrev( $month_name );   // "Jan"
$wp_locale->get_weekday( $weekday_number );     // "Monday" (0 = Sunday)
$wp_locale->get_weekday_abbrev( $weekday_name ); // "Mon"
```

For formatted dates, prefer `wp_date()` / `date_i18n()`, which localise month and
day names automatically.

### 5. English source dialect

Write source strings in standard English. wp-translate handles English targets
locally (no DeepL): `en`/`en_US` use the source as-is, and `en_GB`/`en_AU`/… get
American spellings converted to British automatically (`color` → `colour`).

### Running wp-translate

After changing strings, regenerate translations:

```bash
wp-translate /path/to/this-plugin              # auto-detect locales from languages/
wp-translate /path/to/this-plugin en_GB,fr_FR  # explicit locales
wp-translate /path/to/this-plugin --dry-run    # preview; no API calls, no writes
```

Requires WP-CLI (`wp`) and a DeepL API key at `~/.config/deepl.env`. The tool
regenerates the `.pot` from source, translates new/changed strings for each
locale, and compiles the `.mo` files.
<!-- wp-translate:end -->
