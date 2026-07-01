<?php
/**
 * Express checkout country restrictions (enforcement).
 *
 * @package WooStripeExpressByCountry
 */

namespace Woo_Stripe_Express_By_Country;

defined( 'ABSPATH' ) || die();

/**
 * Enforces the express-checkout country restriction across two layers:
 *
 *   1. Display gate  — hides the wallet buttons from disallowed customers via the
 *      Stripe gateway's per-location filters.
 *   2. Server-side hard guard — rejects the order at checkout if placed via express
 *      checkout from a disallowed country (classic and block / Store API flows).
 *
 * Both layers share the same decision helpers (resolve_country /
 * is_country_allowed) so display and enforcement can never diverge. The configured
 * mode and country list are read from the Settings component via the
 * get_settings_controller() helper, rather than constructor injection.
 *
 * Hooks are registered by the Plugin class, which points the relevant Stripe and
 * checkout hooks at this component's handler methods.
 */
class Restrictions {

	/**
	 * Resolve the effective country for the configured basis, with fallback.
	 *
	 * Returns the country for COUNTRY_BASIS, falling back to the other field when
	 * the preferred one is empty (e.g. virtual orders that carry no shipping
	 * country).
	 *
	 * @param string $shipping_country The shipping country code (may be empty).
	 * @param string $billing_country  The billing country code (may be empty).
	 *
	 * @return string The resolved ISO 3166-1 alpha-2 country code, or '' if none known.
	 */
	public function resolve_country( string $shipping_country, string $billing_country ): string {
		$shipping_country = strtoupper( trim( $shipping_country ) );
		$billing_country  = strtoupper( trim( $billing_country ) );

		if ( 'billing' === COUNTRY_BASIS ) {
			return '' !== $billing_country ? $billing_country : $shipping_country;
		}

		return '' !== $shipping_country ? $shipping_country : $billing_country;
	}

	/**
	 * Determine whether a resolved country may use express checkout.
	 *
	 * Applies the configured mode:
	 *   - Allow-list: allowed only when the country is in the configured list. An
	 *     unknown (empty) country is treated as NOT allowed (fail closed).
	 *   - Block-list: allowed unless the country is in the configured list. An
	 *     unknown (empty) country is allowed, since it is not on the list.
	 *
	 * @param string $country The ISO 3166-1 alpha-2 country code.
	 *
	 * @return bool True if the country is allowed to use express checkout.
	 */
	public function is_country_allowed( string $country ): bool {
		$settings  = get_settings_controller();
		$countries = $settings->get_countries();

		if ( MODE_BLOCK === $settings->get_mode() ) {
			return ! in_array( $country, $countries, true );
		}

		// Allow-list mode: only the listed countries may use express checkout.
		return '' !== $country && in_array( $country, $countries, true );
	}

	/**
	 * Determine whether the current customer is allowed to use express checkout.
	 *
	 * Reads the customer's country from WC()->customer (session / saved address /
	 * WooCommerce geolocation, per store configuration) using the configured basis.
	 * When no customer context is available, the decision falls through to
	 * is_country_allowed() with an empty country, so the mode's default applies.
	 *
	 * @return bool True if the current customer is allowed to use express checkout.
	 */
	public function is_customer_allowed(): bool {
		if ( ! function_exists( 'WC' ) || ! WC()->customer ) {
			$country = '';
		} else {
			$country = $this->resolve_country(
				(string) WC()->customer->get_shipping_country(),
				(string) WC()->customer->get_billing_country()
			);
		}

		$allowed = $this->is_country_allowed( $country );

		/**
		 * Filters whether the current customer may use express checkout.
		 *
		 * Return true to allow, false to deny. Applies to the display gate and, when
		 * enabled, is also the basis for the server-side hard block.
		 *
		 * @param bool   $allowed Whether express checkout is allowed for the customer.
		 * @param string $country The resolved ISO 3166-1 alpha-2 country code ('' if unknown).
		 */
		return (bool) apply_filters( 'wsec_is_customer_allowed', $allowed, $country );
	}

	/**
	 * Filter callback for the cart page express checkout display.
	 *
	 * @param bool $show Whether the gateway would otherwise show the buttons.
	 *
	 * @return bool True to show the buttons, false to hide them.
	 */
	public function filter_show_on_cart( $show ): bool {
		return $show && $this->is_customer_allowed();
	}

	/**
	 * Filter callback for the checkout page express checkout display.
	 *
	 * @param bool $show Whether the gateway would otherwise show the buttons.
	 *
	 * @return bool True to show the buttons, false to hide them.
	 */
	public function filter_show_on_checkout( $show ): bool {
		return $show && $this->is_customer_allowed();
	}

	/**
	 * Filter callback for the product page express checkout display.
	 *
	 * Note: this filter uses inverted logic — returning true HIDES the buttons.
	 *
	 * @param bool $hide Whether the gateway would otherwise hide the buttons.
	 *
	 * @return bool True to hide the buttons, false to show them.
	 */
	public function filter_hide_on_product_page( $hide ): bool {
		return $hide || ! $this->is_customer_allowed();
	}

	/**
	 * The customer-facing message shown when a disallowed express order is blocked.
	 *
	 * @return string
	 */
	public function get_blocked_message(): string {
		return __( 'Express Checkout is not available for your country. Please complete your purchase using the standard checkout.', 'woo-stripe-express-by-country' );
	}

	/**
	 * Whether the current classic-checkout request was placed via express checkout.
	 *
	 * The Stripe gateway includes an `express_checkout_type` field in the posted
	 * data when the order is submitted through an express checkout (wallet) button.
	 *
	 * @return bool
	 */
	private function is_classic_express_checkout_request(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verifies the checkout nonce; we only read a flag.
		return ! empty( $_POST['express_checkout_type'] );
	}

	/**
	 * Hard guard for the classic checkout flow.
	 *
	 * Runs during WooCommerce checkout validation. If the order was placed via
	 * express checkout from a disallowed country, an error is added which aborts
	 * checkout before any payment is taken.
	 *
	 * @param array     $data   The posted checkout fields.
	 * @param \WP_Error $errors The error object to add validation errors to.
	 *
	 * @return void
	 */
	public function guard_classic_checkout( $data, $errors ): void {
		if ( ! $this->is_classic_express_checkout_request() ) {
			return;
		}

		$country = $this->resolve_country(
			isset( $data['shipping_country'] ) ? (string) $data['shipping_country'] : '',
			isset( $data['billing_country'] ) ? (string) $data['billing_country'] : ''
		);

		if ( ! $this->is_country_allowed( $country ) && $errors instanceof \WP_Error ) {
			$errors->add( 'wsec_express_checkout_country', $this->get_blocked_message() );
		}
	}

	/**
	 * Hard guard for the block (Store API) checkout flow.
	 *
	 * Runs after the Stripe gateway records the express-checkout meta but before it
	 * processes the payment. If the order was placed via express checkout from a
	 * disallowed country, a RouteException is thrown to abort before any charge.
	 *
	 * @param \Automattic\WooCommerce\StoreApi\Payments\PaymentContext $context The payment context.
	 *
	 * @return void
	 *
	 * @throws \Automattic\WooCommerce\StoreApi\Exceptions\RouteException When a disallowed express order is detected.
	 * @throws \Exception Fallback when the RouteException class is unavailable.
	 */
	public function guard_store_api_checkout( $context ): void {
		if ( ! is_object( $context ) || 'stripe' !== $context->payment_method ) {
			return;
		}

		$payment_data = isset( $context->payment_data ) ? (array) $context->payment_data : array();
		if ( empty( $payment_data['express_checkout_type'] ) ) {
			return;
		}

		$order = $context->order;
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$country = $this->resolve_country( $order->get_shipping_country(), $order->get_billing_country() );

		if ( $this->is_country_allowed( $country ) ) {
			return;
		}

		// The RouteException class ships with WooCommerce Blocks / Store API. Guard
		// defensively so a missing class can never fatal the checkout request.
		if ( class_exists( '\Automattic\WooCommerce\StoreApi\Exceptions\RouteException' ) ) {
			throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException(
				'wsec_express_checkout_country',
				esc_html( $this->get_blocked_message() ),
				400
			);
		}

		// Fallback: throw a generic exception so the payment still aborts.
		throw new \Exception( esc_html( $this->get_blocked_message() ) );
	}
}
