<?php
/**
 * Plugin Name:          Stripe Express Checkout by Country
 * Plugin URI:           https://headwall-hosting.com/
 * Description:          Restricts the WooCommerce Stripe Express Checkout (Apple Pay / Google Pay) to configurable countries, using an allow-list or block-list.
 * Version:              1.0.0
 * Requires at least:    6.0
 * Requires PHP:         8.0
 * Requires Plugins:     woocommerce, woocommerce-gateway-stripe
 * Author:               Paul Faulkner
 * Author URI:           https://headwall-hosting.com/
 * License:              GPLv2 or later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:          woo-stripe-express-by-country
 * Domain Path:          /languages
 * WC requires at least: 9.0
 * WC tested up to:      10.7
 *
 * @package WooStripeExpressByCountry
 */

defined( 'ABSPATH' ) || die();

const WSEC_NAME    = 'woo-stripe-express-by-country';
const WSEC_VERSION = '1.0.0';

define( 'WSEC_DIR', plugin_dir_path( __FILE__ ) );
define( 'WSEC_URL', plugin_dir_url( __FILE__ ) );
define( 'WSEC_FILE', __FILE__ );
define( 'WSEC_BASENAME', plugin_basename( __FILE__ ) );
define( 'WSEC_ADMIN_TEMPLATES_DIR', trailingslashit( WSEC_DIR . 'admin-templates' ) );

// Load constants and plugin classes.
require_once WSEC_DIR . 'constants.php';
require_once WSEC_DIR . 'functions-private.php';
require_once WSEC_DIR . 'includes/class-settings.php';
require_once WSEC_DIR . 'includes/class-restrictions.php';
require_once WSEC_DIR . 'includes/class-github-updater.php';
require_once WSEC_DIR . 'includes/class-plugin.php';

/**
 * Declare that we are ready for WooCommerce HPOS (High-Performance Order Storage).
 *
 * @link https://github.com/woocommerce/woocommerce/wiki/High-Performance-Order-Storage-Upgrade-Recipe-Book
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

/**
 * Launch the plugin core.
 */
function wsec_plugin_run(): void {
	global $wsec_plugin;

	$wsec_plugin = new Woo_Stripe_Express_By_Country\Plugin();
	$wsec_plugin->run();
}
wsec_plugin_run();
