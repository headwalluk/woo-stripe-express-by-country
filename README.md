# Stripe Express Checkout by Country

![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-21759b)
![WooCommerce](https://img.shields.io/badge/WooCommerce-9.0%2B-96588a)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-777bb4)
![License](https://img.shields.io/badge/license-GPL--2.0--or--later-green)

Make the WooCommerce **Stripe Express Checkout** (Apple Pay / Google Pay / the
Express Checkout Element) conditional on the customer's country.

By default the official WooCommerce Stripe Gateway offers express checkout to
everyone, everywhere. This plugin restricts those wallet buttons to a set of
countries you choose, in one of two modes:

- **Allow-list** — *only* show express checkout for the selected countries.
- **Block-list** — show express checkout for everyone *except* the selected countries.

## Who it's for

Stores that need to steer certain customers into the full WooCommerce checkout
rather than the express wallet flow — for example, UK sellers who must keep
VAT-exemption-eligible customers (UK, Jersey, Guernsey) in the standard checkout,
where the exemption can be applied. Express checkout stays available to everyone
else, so conversions elsewhere are unaffected.

## How it works

- **Display gate** — hides the express-checkout buttons on cart, checkout, and
  product pages for customers whose country isn't permitted. This is the primary
  mechanism and is always on.
- **Optional server-side block** — off by default; when enabled, also rejects a
  disallowed express order at checkout (classic and block / Store API flows).

The customer's country is read from their shipping address (falling back to
billing) via WooCommerce's customer object, so your WooCommerce geolocation
settings apply.

## Requirements

- WordPress 6.0+
- WooCommerce 9.0+
- [WooCommerce Stripe Gateway](https://wordpress.org/plugins/woocommerce-gateway-stripe/) (provides express checkout and its display filters)
- PHP 8.0+

## Installation

1. Ensure WooCommerce and the WooCommerce Stripe Gateway are installed and active.
2. Upload the plugin to `/wp-content/plugins/` and activate it.
3. Go to **WooCommerce → Stripe Express by Country**, choose a mode, and select the countries.

## Documentation

- [Settings](docs/settings.md) — every option explained, including empty-list behaviour and a worked example.
- [Hooks](docs/hooks.md) — the `wsec_is_customer_allowed` filter for custom logic, plus the WooCommerce/Stripe hooks the plugin uses.

## Development

See [`CLAUDE.md`](CLAUDE.md) for architecture and conventions, and
[`dev-notes/00-project-tracker.md`](dev-notes/00-project-tracker.md) for the
roadmap. The codebase follows the WordPress Coding Standards — run `phpcs` before
committing.

## License

GPL-2.0-or-later. See [`LICENSE`](LICENSE).
