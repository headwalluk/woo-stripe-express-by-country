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

Those customers get express hidden and flow through the standard checkout;
everyone else keeps express checkout.

## Roadmap

The display gate hides the buttons but does not, by itself, prevent a determined
customer from completing an express payment whose wallet country differs from
what was shown. A stricter, optional **server-side block** is planned for a
future release; it is present in the codebase but disabled for now.
