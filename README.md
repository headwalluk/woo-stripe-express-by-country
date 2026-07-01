# Stripe Express Checkout by Country

A small WordPress/WooCommerce plugin that makes the **Stripe Express Checkout**
(Apple Pay / Google Pay / the Express Checkout Element) conditional on the
customer's country.

It works alongside the official **WooCommerce Stripe Gateway** plugin, which by
default offers express checkout to everyone, everywhere. This plugin restricts
those wallet buttons to a configured set of countries, in one of two modes:

- **Allow-list** — *"Only show express checkout for these countries."*
- **Block-list** — *"Show express checkout for all customers, except these countries."*

## How it works

Two enforcement layers:

1. **Display gate** — hides the wallet buttons from disallowed customers via the
   Stripe gateway's per-location filters (cart, checkout, product page).
2. **Server-side hard guard** — rejects the order at checkout if it was placed via
   express checkout from a disallowed country, even if the customer changed their
   address inside the wallet sheet. Covers both the classic and block / Store API
   checkout flows.

The customer's country is resolved as **shipping, falling back to billing**, read
through WooCommerce's own customer object (so WooCommerce's geolocation fallback
applies where the store is configured for it).

## Requirements

- WordPress 6.0+
- WooCommerce 9.0+
- WooCommerce Stripe Gateway (provides the express checkout and its display filters)
- PHP 8.0+

## Configuration

**WooCommerce → Stripe Express by Country.** Choose a mode and select the
countries it applies to.

## Development

See `CLAUDE.md` for architecture and conventions, and
`dev-notes/00-project-tracker.md` for the roadmap. Run `phpcs` before committing.
