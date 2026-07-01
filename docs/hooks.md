# Hooks

## Filters this plugin provides

### `wsec_is_customer_allowed`

Filters the final decision on whether the current customer may use express
checkout. This is the single decision the plugin acts on: it drives the display
gate (whether the buttons are shown) and, when the server-side block is enabled,
the checkout guard as well.

**Parameters**

| Param | Type | Description |
|-------|------|-------------|
| `$allowed` | `bool` | Whether express checkout is allowed for the customer, based on the configured mode and country list. |
| `$country` | `string` | The resolved ISO 3166-1 alpha-2 country code (`''` if unknown). Shipping, falling back to billing. |

**Return** `bool` — `true` to allow express checkout, `false` to deny.

**Examples**

Always allow logged-in wholesale customers, regardless of country:

```php
add_filter( 'wsec_is_customer_allowed', function ( $allowed, $country ) {
	if ( current_user_can( 'wholesale_customer' ) ) {
		return true;
	}
	return $allowed;
}, 10, 2 );
```

Additionally block an extra country without changing the saved settings:

```php
add_filter( 'wsec_is_customer_allowed', function ( $allowed, $country ) {
	if ( 'IE' === $country ) {
		return false;
	}
	return $allowed;
}, 10, 2 );
```

## Third-party hooks this plugin consumes

For reference — these belong to WooCommerce and the WooCommerce Stripe Gateway;
the plugin attaches its logic to them.

### Display gate (WooCommerce Stripe Gateway)

| Hook | Purpose |
|------|---------|
| `wc_stripe_show_payment_request_on_cart` | Show/hide express checkout on the cart. |
| `wc_stripe_show_payment_request_on_checkout` | Show/hide express checkout on the checkout. |
| `wc_stripe_hide_payment_request_on_product_page` | Show/hide express checkout on product pages (inverted: `true` hides). |

### Server-side hard block (WooCommerce, only when enabled)

| Hook | Purpose |
|------|---------|
| `woocommerce_after_checkout_validation` | Reject a disallowed express order in the classic checkout flow. |
| `woocommerce_rest_checkout_process_payment_with_context` | Reject a disallowed express order in the block / Store API flow (priority 50). |
