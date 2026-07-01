# Stripe Express by Country — Project Tracker

**Version:** 1.0.0
**Last Updated:** 1 July 2026
**Status:** 1.0.0 released (display-gate-only; server-side block deferred)

---

## Overview

A small WordPress/WooCommerce plugin that makes the **Stripe Express Checkout**
(Apple Pay / Google Pay / the Express Checkout Element) **conditional on the
customer's country**. It sits alongside the official *WooCommerce Stripe Gateway*
plugin and restricts where those wallet buttons are offered.

By default the Stripe plugin exposes express checkout to everyone, everywhere
(cart, checkout, product pages). This plugin lets the store operator restrict
that to a configured set of countries, in one of two modes.

**Origin:** productionises the working prototype at
`acth.headwall.tech/.../themes/activehands/express-checkout-restrictions.php`,
which is currently hardcoded to US-only via a theme snippet.

---

## Requirements & Decisions

Confirmed with the client on 1 July 2026:

1. **Admin configuration** — a simple standalone admin settings page (not a
   PHP constant). Non-technical operator must be able to change the country list.
2. **Two modes**, switchable via the settings page:
   - **Allow-list** — "Only show express checkout for these countries"
   - **Block-list** — "Show express checkout for all customers, except these
     countries"
3. **Both enforcement layers** (from the prototype):
   - **Layer 1 — Display gate:** hide the buttons via the Stripe gateway's
     per-location filters (`wc_stripe_show_payment_request_on_cart`,
     `wc_stripe_show_payment_request_on_checkout`,
     `wc_stripe_hide_payment_request_on_product_page`).
   - **Layer 2 — Server-side hard guard:** reject the order at checkout
     validation if placed via express checkout from a disallowed country, even
     if the customer changed their address inside the wallet sheet. Covers both
     the classic checkout flow and the block / Store API checkout flow.
4. **Country basis:** shipping country, falling back to billing when shipping is
   empty. Read via `WC()->customer` so WooCommerce supplies the value from its
   own precedence chain — session / saved address, and the built-in **geolocation
   (geo-IP) fallback** when the store is configured for it. We do not implement
   our own geo lookup; we let Woo resolve it. Kept as an internal constant, not
   exposed in the UI.
5. **Fail-closed** on unknown country: mirror the prototype's safe default so an
   unresolved country is treated as *not* express-eligible in allow-list mode.
6. **Admin page placement:** a dedicated submenu item under the **WooCommerce**
   top-level admin menu (`add_submenu_page` with parent `woocommerce`) — its own
   page, **not** a tab on the WooCommerce → Settings screen.
7. **Empty-list semantics:** an empty **allow-list** hides express from everyone;
   an empty **block-list** shows express to everyone. These are the intended
   "off" states for each mode.
8. **Hard block is optional, default OFF** (revised 1 July 2026). The display gate
   is the primary mechanism. Real-world testing showed the country signals are
   inherently fuzzy — IP geolocation, typed billing/shipping, and the address the
   customer resolves *inside* the Apple/Google Pay wallet sheet routinely disagree
   — so an always-on hard block reintroduces a "button shown, then payment
   rejected" experience that hurts the conversion goal the plugin exists to serve.
   The hard block is now an opt-in setting (`OPT_HARD_BLOCK`) for stores that need
   a strict guarantee. Missing a VAT exemption is recoverable; a rejected wallet
   click is a lost sale — so the default protects the sale.
   - Primary client use case: **block-list** of UK VAT territories — `GB`, `JE`
     (Jersey), `GG` (Guernsey) — because those customers need the full checkout to
     claim VAT exemption. Display gate only; hard block left off.
9. **Display signal:** the display gate keeps using `WC()->customer` country
   (billing/shipping, seeded by geolocation / store base) — not a bespoke geo-IP
   lookup. Simple and adequate for the block-list use case.

### Naming / conventions (confirmed)

Following the Bullfix ERP house style (WPCS via `phpcs`, namespaced, PHP 8.0+,
no `declare(strict_types=1)`, `constants.php` for all magic strings, class-per-
file, thorough PHPDoc, HPOS-compatible).

- **Plugin Name:** Stripe Express Checkout by Country
- **Text Domain:** `woo-stripe-express-by-country`
- **Namespace:** `Woo_Stripe_Express_By_Country`
- **Prefixes (phpcs):** `woo_stripe_express_by_country`, `wsec`, `WSEC`,
  `Woo_Stripe_Express_By_Country`
- **Option keys:** `OPT_` (mode, country list), defaults `DEF_`

---

## Architecture Plan

```
woo-stripe-express-by-country/
├── woo-stripe-express-by-country.php   # Header, constants, requires, bootstrap
├── constants.php                       # OPT_/DEF_ keys, mode values, page slug
├── functions-private.php               # get_plugin() / get_settings() accessors
├── phpcs.xml                           # WPCS ruleset + prefixes (copy bullfix)
├── includes/
│   ├── class-plugin.php                # Orchestrator: registers all hooks (run/init/admin_init/admin_menu)
│   ├── class-settings.php              # Admin page, save handler, get_mode()/get_countries()
│   └── class-restrictions.php          # Layer 1 filters + Layer 2 guards + is_customer_allowed()
├── admin-templates/
│   └── settings-page.php               # Mode radio + country multi-select
├── languages/
│   └── .gitkeep
├── README.md
├── readme.txt
├── CHANGELOG.md
├── CLAUDE.md
└── dev-notes/
    └── 00-project-tracker.md
```

**Core decision logic** (shared by both layers), in `class-restrictions.php`:

```
resolve_country(shipping, billing) -> country code (shipping w/ billing fallback)
is_country_allowed(country):
    mode = allow-list  -> country IN configured list
    mode = block-list  -> country NOT IN configured list
    (empty/unknown country -> fail closed per mode)
```

---

## Milestones

### Milestone 1: Plugin Scaffold ✅

**Status:** Complete (pending review) · **Priority:** High

- [x] Create main plugin file with WP/WC header (Requires Plugins: woocommerce, WC requires at least)
- [x] Add `constants.php` (namespace, `OPT_MODE`, `OPT_COUNTRIES`, `DEF_MODE`, mode constants `MODE_ALLOW`/`MODE_BLOCK`, `SETTINGS_PAGE_SLUG`, `COUNTRY_BASIS`)
- [x] Copy & adapt `phpcs.xml` with this plugin's prefixes
- [x] Declare HPOS compatibility (`before_woocommerce_init`)
- [x] Add `class-plugin.php` orchestrator with `run()` — loads text domain, lazily instantiates + registers components
- [x] Add `Settings` / `Restrictions` loadable stubs (implemented in M2–M4)
- [x] `.gitignore`, `README.md`, `CLAUDE.md`, `readme.txt`, `CHANGELOG.md`, `languages/`
- [x] Run `phpcs` — zero violations (5/5 files clean); `php -l` clean on all files

### Milestone 2: Settings Page ✅

**Status:** Complete (pending review) · **Priority:** High

- [x] `class-settings.php` — getters `get_mode()` (validated), `get_countries()` (validated array of ISO codes), `get_all_countries()` (WC list), nonce action/field + `manage_woocommerce` cap
- [x] Admin submenu page under the WooCommerce top-level menu (`add_submenu_page`, parent `woocommerce`, `manage_woocommerce` cap) — its own page, not a WC Settings tab
- [x] `admin-templates/settings-page.php`:
  - [x] Mode selector (radio): allow-list vs block-list, with the plain-English labels
  - [x] Country multi-select populated from `WC()->countries->get_countries()`
- [x] Save handler (`maybe_save_settings` → `save_settings`): nonce verification, cap check, sanitize country codes against the WC list, sanitize mode against allowed values
- [x] Output escaping on all fields; sensible defaults on first load; `_x()` / translators-friendly strings
- [x] Run `phpcs` — zero violations (7/7 files clean)

### Milestone 3: Enforcement — Layer 1 (Display Gate) ✅

**Status:** Complete (pending review) · **Priority:** High

- [x] Port `is_customer_allowed()` — read `WC()->customer` country, apply mode logic
- [x] `resolve_country()` + `is_country_allowed()` honouring allow/block mode
- [x] Hook `wc_stripe_show_payment_request_on_cart`
- [x] Hook `wc_stripe_show_payment_request_on_checkout`
- [x] Hook `wc_stripe_hide_payment_request_on_product_page` (inverted logic)
- [x] Fail-closed when country unknown (allow-list mode); permissive for unknown in block-list mode
- [x] Run `phpcs` — zero violations (7/7 files clean)

### Milestone 4: Enforcement — Layer 2 (Optional Server-side Hard Guard) ⏸️

**Status:** Built, but DEFERRED to post-1.0.0 · **Priority:** High

Revised to be **opt-in, default OFF** — see Decision 8. Then, to make 1.0.0
immediately shippable, the whole feature was gated behind the
`IS_SERVER_HARDBLOCK_AVAILABLE` constant (currently `false`) so it is invisible
and inert in 1.0.0. The code below is complete; re-enabling is tracked in
Milestone 6.

- [x] `OPT_HARD_BLOCK` option + `Settings::is_hard_block_enabled()` getter + save handling
- [x] Settings page checkbox: "Also reject disallowed express-checkout orders on the server" (with a description explaining the wallet-country caveat)
- [x] Guards registered in `Plugin::run()` **only when the setting is enabled**
- [x] Classic checkout: `woocommerce_after_checkout_validation` — detect `express_checkout_type` in POST, reject disallowed country
- [x] Block/Store API: `woocommerce_rest_checkout_process_payment_with_context` (priority 50) — detect express meta, throw `RouteException` before charge (with generic `Exception` fallback)
- [x] Reuse the shared decision helpers so both layers stay consistent
- [x] Translatable customer-facing blocked message
- [x] Run `phpcs` — zero violations (7/7 files clean)

### Milestone 5: Testing & Release Prep (1.0.0) 🚧

**Status:** In Progress · **Priority:** Medium

- [x] Live-tested allow-list and block-list display gating on classic checkout
- [x] `phpcs` clean; bump to 1.0.0 (plugin header, `WSEC_VERSION`, `readme.txt`)
- [x] CHANGELOG 1.0.0 entry; README + `docs/` finalised
- [x] i18n: strings verified against the wp-translate rules; `.pot` + `.po`/`.mo` built for en_GB, fr_FR, de_DE, es_ES, it_IT, pl_PL, nl_NL
- [x] Test with no countries configured in each mode (empty-list defaults verified in browser)
- [x] Confirm no fatal when the Stripe plugin is inactive / its filters are absent (verified: classic checkout works, other gateways shown, logs clear)
- [x] Tag `v1.0.0`; release workflow green; zip contents verified clean (15 shipping files, dev/docs stripped)

### Milestone 6: GitHub Self-Update & Release Workflow (1.0.0) ✅

**Status:** Complete (pending release verification) · **Priority:** Medium

Adapted from the shared Headwall updater used on other plugins.

- [x] `includes/class-github-updater.php` (namespace, constants, `wsec_updater_enabled` filter)
- [x] `UPDATER_GITHUB_REPO` / `UPDATER_CACHE_KEY` / `UPDATER_CACHE_TTL` constants + `WSEC_BASENAME`
- [x] Instantiated in `Plugin::run()`; requires added to the main file
- [x] `.github/workflows/release.yml` (builds `{slug}.zip` + versioned zip on `v*.*.*` tag)
- [x] `.distignore` for the shipped zip (expanded to strip docs / README / dev files)
- [x] Tagged `v1.0.0`; workflow builds a clean zip (verified contents)
- [ ] Final: install the built zip on a test site to confirm activation + self-update

### Milestone 7: Server-side Hard Block (post-1.0.0) 📋

**Status:** Not Started · **Priority:** Low

Re-enable the Layer 2 feature built in Milestone 4.

- [ ] Flip `IS_SERVER_HARDBLOCK_AVAILABLE` to `true`
- [ ] Re-verify the settings checkbox saves and the guards register when enabled
- [ ] Test the wallet-sheet country swap is caught on classic + block checkout
- [ ] Restore the server-side sections in `docs/settings.md` and `docs/hooks.md`
- [ ] `phpcs` clean; changelog; version bump

---

## Open Questions

_All planning questions resolved on 1 July 2026 — see Requirements & Decisions._

---

## Reference

- **Prototype:** `acth.headwall.tech/web/wp-content/themes/activehands/express-checkout-restrictions.php`
- **House style:** `bullfix-erp/` (WPCS, constants, class structure, settings pattern)
- **Depends on:** WooCommerce Stripe Gateway plugin (provides the `wc_stripe_*` filters)
