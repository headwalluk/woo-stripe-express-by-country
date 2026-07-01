<?php
/**
 * Plugin settings.
 *
 * @package WooStripeExpressByCountry
 */

namespace Woo_Stripe_Express_By_Country;

defined( 'ABSPATH' ) || die();

/**
 * Settings management.
 *
 * Provides the admin submenu page (under the WooCommerce menu), the save handler,
 * and type-safe getters for the configured mode and country list. The Restrictions
 * component reads these getters (via the get_settings_controller() helper) to
 * decide who may use express checkout.
 *
 * Hooks are registered by the Plugin class, which points the relevant WP hooks at
 * this component's handler methods.
 */
class Settings {

	/**
	 * Settings action name for the nonce.
	 *
	 * @var string
	 */
	public string $settings_action;

	/**
	 * Settings nonce field name.
	 *
	 * @var string
	 */
	public string $settings_nonce;

	/**
	 * Capability required to view and save the settings page.
	 *
	 * `manage_woocommerce` so a WooCommerce shop_manager can configure the
	 * restriction without needing full administrator privileges.
	 *
	 * @var string
	 */
	protected string $settings_cap = 'manage_woocommerce';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->settings_action = 'wsecsaveact' . WSEC_NAME;
		$this->settings_nonce  = 'wsecsavenonce' . WSEC_NAME;
	}

	/**
	 * Get the capability required to manage the settings page.
	 *
	 * @return string
	 */
	public function get_settings_cap(): string {
		return $this->settings_cap;
	}

	/**
	 * Get the active restriction mode.
	 *
	 * Falls back to DEF_MODE when the stored value is missing or invalid.
	 *
	 * @return string One of MODE_ALLOW or MODE_BLOCK.
	 */
	public function get_mode(): string {
		$mode = (string) get_option( OPT_MODE, DEF_MODE );

		return in_array( $mode, array( MODE_ALLOW, MODE_BLOCK ), true ) ? $mode : DEF_MODE;
	}

	/**
	 * Get the configured country list.
	 *
	 * @return array<int, string> Uppercase ISO 3166-1 alpha-2 country codes.
	 */
	public function get_countries(): array {
		$stored = get_option( OPT_COUNTRIES, array() );

		if ( ! is_array( $stored ) ) {
			return array();
		}

		$codes = array_map(
			static fn( $code ) => strtoupper( sanitize_text_field( (string) $code ) ),
			$stored
		);

		return array_values( array_unique( array_filter( $codes ) ) );
	}

	/**
	 * Whether the server-side hard block is enabled.
	 *
	 * @return bool
	 */
	public function is_hard_block_enabled(): bool {
		return filter_var( get_option( OPT_HARD_BLOCK, false ), FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Get the full WooCommerce country list (code => name).
	 *
	 * @return array<string, string>
	 */
	public function get_all_countries(): array {
		if ( function_exists( 'WC' ) && WC()->countries ) {
			return WC()->countries->get_countries();
		}

		return array();
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( $this->settings_cap ) ) {
			printf( '<p>%s</p>', esc_html__( 'You are not allowed to manage these settings.', 'woo-stripe-express-by-country' ) );
			return;
		}

		// Make the settings object available to the template.
		$settings = $this;

		include WSEC_ADMIN_TEMPLATES_DIR . 'settings-page.php';
	}

	/**
	 * Save the settings if the settings form was submitted.
	 *
	 * Verifies the nonce and capability before writing anything.
	 *
	 * @return void
	 */
	public function maybe_save_settings(): void {
		if ( ! is_admin() || wp_doing_ajax() ) {
			return;
		}

		if ( ! isset( $_POST[ $this->settings_nonce ] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST[ $this->settings_nonce ] ) );

		if ( ! wp_verify_nonce( $nonce, $this->settings_action ) ) {
			return;
		}

		if ( ! current_user_can( $this->settings_cap ) ) {
			return;
		}

		$this->save_settings();
	}

	/**
	 * Persist the submitted settings.
	 *
	 * Authentication, authorisation, and the nonce have already been checked by
	 * {@see maybe_save_settings()}, so we only parse and sanitise $_POST here.
	 *
	 * @return void
	 */
	private function save_settings(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified in maybe_save_settings().

		// Mode — restrict to the two known values.
		$mode = isset( $_POST[ OPT_MODE ] ) ? sanitize_text_field( wp_unslash( $_POST[ OPT_MODE ] ) ) : DEF_MODE;
		if ( ! in_array( $mode, array( MODE_ALLOW, MODE_BLOCK ), true ) ) {
			$mode = DEF_MODE;
		}
		update_option( OPT_MODE, $mode );

		// Countries — keep only codes that exist in WooCommerce's country list.
		$submitted = isset( $_POST[ OPT_COUNTRIES ] )
			? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST[ OPT_COUNTRIES ] ) )
			: array();
		$submitted = array_map( 'strtoupper', $submitted );

		$valid     = array_keys( $this->get_all_countries() );
		$countries = array_values( array_unique( array_intersect( $submitted, $valid ) ) );

		update_option( OPT_COUNTRIES, $countries );

		// Server-side hard block (checkbox: present = enabled).
		update_option( OPT_HARD_BLOCK, isset( $_POST[ OPT_HARD_BLOCK ] ) ? '1' : '' );

		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}
}
