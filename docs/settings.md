# Settings

All settings live on one screen: **WooCommerce → Stripe Express by Country**
(capability: `manage_woocommerce`).

## Mode

Choose how the country list is interpreted:

| Mode | Meaning |
|------|---------|
| **Only show express checkout for the selected countries** (allow-list) | Express checkout is offered **only** to customers in the selected countries. Everyone else uses the standard checkout. |
| **Show express checkout for all customers, except the selected countries** (block-list) | Express checkout is offered to everyone **except** customers in the selected countries. |

## Countries

A searchable multi-select (WooCommerce's SelectWoo control) of every country
WooCommerce knows about. The meaning of the selection depends on the mode above.

**Empty-list behaviour:**

| Mode | Empty list means |
|------|------------------|
| Allow-list | Express checkout is hidden from **everyone** (nothing is allowed). |
| Block-list | Express checkout is shown to **everyone** (nothing is blocked). |

## Server-side block (optional, default off)

> *Also reject disallowed express-checkout orders on the server*

When **off** (default), the plugin only hides the express-checkout buttons — the
"display gate". This is the recommended setting for most stores.

When **on**, the plugin *additionally* rejects a disallowed order during checkout
validation (both classic and block / Store API flows), even if the buttons were
shown.

**Why it is off by default:** the customer's country is a fuzzy signal. Their IP
geolocation, the address they type, and the address their Apple/Google Pay wallet
resolves *inside the payment sheet* can all differ. With the hard block on, a
customer can be shown the button, tap it, and then have the payment rejected —
which hurts conversion. Enable it only when you need a strict guarantee that a
disallowed order can never complete via express checkout.

## How the customer's country is determined

The country is taken from the customer's **shipping** address, falling back to
**billing** when shipping is empty. It is read through WooCommerce's own customer
object, so WooCommerce's geolocation / store-base-country behaviour applies as
configured under **WooCommerce → Settings → General → Default customer location**.

## Example: UK VAT-exemption stores

A common setup: keep UK / Channel Islands customers in the full checkout so they
can claim VAT exemption (which express checkout cannot represent).

- **Mode:** block-list
- **Countries:** United Kingdom (`GB`), Jersey (`JE`), Guernsey (`GG`)
- **Server-side block:** off

Those customers get express hidden and flow through the standard checkout;
everyone else keeps express checkout.
