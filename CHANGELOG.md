# Changelog

All notable changes to this plugin are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/), and this
project adheres to [Semantic Versioning](https://semver.org/).

## [1.0.0] - 2026-07-01

Initial release.

### Added
- Restrict the WooCommerce Stripe express checkout (Apple Pay / Google Pay) to
  configurable countries using an allow-list or block-list.
- Settings page under WooCommerce → Stripe Express by Country: mode selector and
  a searchable country multi-select (SelectWoo), with nonce-verified,
  capability-gated, validated saving.
- Display gate: hides the express-checkout buttons on cart, checkout, and product
  pages from customers whose country is not permitted by the configured mode.
- `wsec_is_customer_allowed` filter so developers can override the allow/deny
  decision with custom logic.
- Self-updates from GitHub Releases: in-plugin updater plus a tagged-release
  build workflow.
- HPOS compatibility declaration.
- Documentation: `README.md` with badges, `docs/settings.md`, `docs/hooks.md`,
  and a `LICENSE` file (GPL-2.0).

### Notes
- A server-side "hard block" enforcement layer exists in the codebase but is
  disabled behind the `IS_SERVER_HARDBLOCK_AVAILABLE` feature flag, deferred to a
  future release. 1.0.0 ships as display-gate-only.
