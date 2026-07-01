# Changelog

All notable changes to this plugin are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/), and this
project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added
- Plugin scaffold: main plugin file, constants, `phpcs.xml`, `Plugin`
  orchestrator, and `Settings` / `Restrictions` component stubs (Milestone 1).
- HPOS compatibility declaration.
- Settings page under WooCommerce → Stripe Express by Country: mode selector
  (allow-list / block-list) and a country multi-select, with nonce-verified,
  capability-gated, validated saving (Milestone 2).
- Display gate: hides the Stripe express-checkout buttons on cart, checkout, and
  product pages from customers whose country is not permitted by the configured
  mode (Milestone 3).
- Optional server-side hard block (`OPT_HARD_BLOCK`, default off): when enabled,
  rejects a disallowed express-checkout order at validation for both classic and
  block / Store API checkout flows (Milestone 4).
