<?php
/**
 * Plugin-scope functions.
 *
 * Convenience accessors for the global plugin instance and its components, so
 * hook handlers can reach configuration without constructor injection.
 *
 * @package WooStripeExpressByCountry
 */

namespace Woo_Stripe_Express_By_Country;

defined( 'ABSPATH' ) || die();

/**
 * Get a handle to the core plugin object.
 *
 * @return Plugin
 */
function get_plugin(): Plugin {
	global $wsec_plugin;
	return $wsec_plugin;
}

/**
 * Get a handle to the plugin's settings controller.
 *
 * Named `get_settings_controller()` rather than `get_settings()` to avoid
 * colliding with WordPress core's deprecated `get_settings()` function.
 *
 * @return Settings
 */
function get_settings_controller(): Settings {
	global $wsec_plugin;
	return $wsec_plugin->get_settings();
}
