<?php
/**
 * Plugin core.
 *
 * @package WooStripeExpressByCountry
 */

namespace Woo_Stripe_Express_By_Country;

defined( 'ABSPATH' ) || die();

/**
 * The plugin's core functionality.
 *
 * Wires the plugin together. All hooks are registered here — in run() for early
 * hooks, and in the init()/admin_init()/admin_menu() handlers for the rest — so
 * there is a single, predictable place to trace how the plugin is bootstrapped.
 * The behaviour lives in the component classes (Settings, Restrictions), whose
 * handler methods are pointed at from here.
 */
class Plugin {

	/**
	 * Set up the plugin's hook handlers. This runs before WP has initialised.
	 *
	 * @return void
	 */
	public function run(): void {
		$this->settings = new Settings();

		// Self-updates from GitHub Releases. The updater registers its own hooks
		// into the WordPress plugin-update system from its constructor.
		$this->github_updater = new Github_Updater();

		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

		// Layer 1 — display gate: hide the express-checkout buttons from
		// disallowed customers via the Stripe gateway's per-location filters.
		$restrictions = $this->get_restrictions();
		add_filter( 'wc_stripe_show_payment_request_on_cart', array( $restrictions, 'filter_show_on_cart' ) );
		add_filter( 'wc_stripe_show_payment_request_on_checkout', array( $restrictions, 'filter_show_on_checkout' ) );
		add_filter( 'wc_stripe_hide_payment_request_on_product_page', array( $restrictions, 'filter_hide_on_product_page' ) );

		// Layer 2 — optional server-side hard block: reject a disallowed express
		// order at checkout, covering the case where the customer's wallet country
		// differs from the country shown. Deferred to a post-1.0.0 release (gated
		// behind IS_SERVER_HARDBLOCK_AVAILABLE); when available, only registered
		// when the operator has explicitly enabled it.
		if ( IS_SERVER_HARDBLOCK_AVAILABLE && $this->settings->is_hard_block_enabled() ) {
			add_action( 'woocommerce_after_checkout_validation', array( $restrictions, 'guard_classic_checkout' ), 10, 2 );
			add_action( 'woocommerce_rest_checkout_process_payment_with_context', array( $restrictions, 'guard_store_api_checkout' ), 50 );
		}
	}

	/**
	 * WP `init` action handler.
	 *
	 * @return void
	 */
	public function init(): void {
		$this->load_textdomain();
	}

	/**
	 * WP `admin_init` action handler.
	 *
	 * @return void
	 */
	public function admin_init(): void {
		$this->settings->maybe_save_settings();
	}

	/**
	 * The settings page hook suffix, as returned by add_submenu_page().
	 *
	 * @var string
	 */
	private string $settings_page_hook = '';

	/**
	 * WP `admin_menu` action handler.
	 *
	 * Registers the settings page as a submenu item under the WooCommerce
	 * top-level menu (not a tab on the WooCommerce → Settings screen).
	 *
	 * @return void
	 */
	public function admin_menu(): void {
		$this->settings_page_hook = (string) add_submenu_page(
			'woocommerce',
			__( 'Stripe Express by Country', 'woo-stripe-express-by-country' ),
			__( 'Stripe Express by Country', 'woo-stripe-express-by-country' ),
			$this->settings->get_settings_cap(),
			SETTINGS_PAGE_SLUG,
			array( $this->settings, 'render_settings_page' )
		);
	}

	/**
	 * WP `admin_enqueue_scripts` action handler.
	 *
	 * Loads WooCommerce's enhanced-select (SelectWoo / Select2) script and admin
	 * styles on our settings page only, so the country picker renders as a
	 * searchable multi-select.
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 *
	 * @return void
	 */
	public function admin_enqueue_scripts( string $hook_suffix ): void {
		if ( '' === $this->settings_page_hook || $hook_suffix !== $this->settings_page_hook ) {
			return;
		}

		wp_enqueue_script( 'wc-enhanced-select' );
		wp_enqueue_style( 'woocommerce_admin_styles' );
	}

	/**
	 * Load the plugin text domain for translations.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'woo-stripe-express-by-country',
			false,
			dirname( plugin_basename( WSEC_FILE ) ) . '/languages'
		);
	}

	/**
	 * GitHub self-updater.
	 *
	 * @var Github_Updater
	 */
	private Github_Updater $github_updater;

	/**
	 * Settings controller.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Get the plugin's settings controller.
	 *
	 * @return Settings
	 */
	public function get_settings(): Settings {
		return $this->settings;
	}

	/**
	 * Restrictions controller.
	 *
	 * @var Restrictions|null
	 */
	private ?Restrictions $restrictions = null;

	/**
	 * Get the plugin's restrictions controller, instantiating it on first use.
	 *
	 * @return Restrictions
	 */
	public function get_restrictions(): Restrictions {
		if ( is_null( $this->restrictions ) ) {
			$this->restrictions = new Restrictions();
		}

		return $this->restrictions;
	}
}
