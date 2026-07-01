=== Stripe Express Checkout by Country ===
Contributors: headwall
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Restrict the WooCommerce Stripe Express Checkout (Apple Pay / Google Pay) to configurable countries, using an allow-list or block-list.

== Description ==

By default the WooCommerce Stripe Gateway offers express checkout (Apple Pay,
Google Pay, the Express Checkout Element) to everyone, everywhere. This plugin
makes those wallet buttons conditional on the customer's country, in one of two
modes:

* Allow-list — only show express checkout for the selected countries.
* Block-list — show express checkout for everyone except the selected countries.

Two enforcement layers are applied: a display gate that hides the buttons, and a
server-side hard guard that rejects a disallowed express-checkout order at
checkout (classic and block / Store API flows).

== Installation ==

1. Ensure WooCommerce and the WooCommerce Stripe Gateway are installed and active.
2. Upload the plugin to `/wp-content/plugins/` and activate it.
3. Go to WooCommerce → Stripe Express by Country, pick a mode, and select the countries.

== Changelog ==

= 1.0.0 =
* Initial release.
