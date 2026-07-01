<?php
/**
 * Settings page.
 *
 * Renders the restriction mode selector and the country multi-select.
 *
 * Expects a `$settings` variable (the Settings instance) in scope, supplied by
 * Settings::render_settings_page().
 *
 * @package WooStripeExpressByCountry
 */

namespace Woo_Stripe_Express_By_Country;

defined( 'ABSPATH' ) || die();

$wsec_mode      = $settings->get_mode();
$wsec_selected  = $settings->get_countries();
$wsec_countries = $settings->get_all_countries();

printf( '<div class="wrap"><h1>%s</h1>', esc_html( get_admin_page_title() ) );

printf(
	'<p>%s</p>',
	esc_html__( 'Control which customers are offered the Stripe Express Checkout (Apple Pay / Google Pay) based on their country. The customer country is taken from their shipping address, falling back to billing.', 'woo-stripe-express-by-country' )
);

echo '<form method="post" action="">';
wp_nonce_field( $settings->settings_action, $settings->settings_nonce );

echo '<table class="form-table" role="presentation">';

// Restriction mode.
echo '<tr><th scope="row">';
printf( '<span>%s</span>', esc_html_x( 'Mode', 'restriction mode setting', 'woo-stripe-express-by-country' ) );
echo '</th><td><fieldset>';

printf(
	'<label><input type="radio" name="%1$s" value="%2$s" %3$s /> %4$s</label><br />',
	esc_attr( OPT_MODE ),
	esc_attr( MODE_ALLOW ),
	checked( MODE_ALLOW, $wsec_mode, false ),
	esc_html__( 'Only show express checkout for the selected countries', 'woo-stripe-express-by-country' )
);

printf(
	'<label><input type="radio" name="%1$s" value="%2$s" %3$s /> %4$s</label>',
	esc_attr( OPT_MODE ),
	esc_attr( MODE_BLOCK ),
	checked( MODE_BLOCK, $wsec_mode, false ),
	esc_html__( 'Show express checkout for all customers, except the selected countries', 'woo-stripe-express-by-country' )
);

echo '</fieldset></td></tr>';

// Countries.
echo '<tr><th scope="row">';
printf(
	'<label for="%s">%s</label>',
	esc_attr( OPT_COUNTRIES ),
	esc_html_x( 'Countries', 'country list setting', 'woo-stripe-express-by-country' )
);
echo '</th><td><fieldset>';

if ( empty( $wsec_countries ) ) {
	printf(
		'<p class="description">%s</p>',
		esc_html__( 'The WooCommerce country list is unavailable. Please ensure WooCommerce is active.', 'woo-stripe-express-by-country' )
	);
} else {
	printf(
		'<select name="%1$s[]" id="%1$s" multiple class="wc-enhanced-select" data-placeholder="%2$s" style="width: 25em;">',
		esc_attr( OPT_COUNTRIES ),
		esc_attr__( 'Choose countries…', 'woo-stripe-express-by-country' )
	);
	foreach ( $wsec_countries as $wsec_code => $wsec_name ) {
		printf(
			'<option value="%1$s" %2$s>%3$s</option>',
			esc_attr( $wsec_code ),
			selected( in_array( $wsec_code, $wsec_selected, true ), true, false ),
			esc_html( $wsec_name )
		);
	}
	echo '</select>';

	printf(
		'<p class="description">%s</p>',
		esc_html__( 'Search for and select one or more countries. The selection is interpreted according to the mode above.', 'woo-stripe-express-by-country' )
	);
}

echo '</fieldset></td></tr>';

// Server-side hard block (deferred to a post-1.0.0 release; see constants.php).
if ( IS_SERVER_HARDBLOCK_AVAILABLE ) {
	$wsec_hard_block = $settings->is_hard_block_enabled();

	echo '<tr><th scope="row">';
	printf( '<span>%s</span>', esc_html_x( 'Server-side block', 'hard block setting', 'woo-stripe-express-by-country' ) );
	echo '</th><td><fieldset>';

	printf(
		'<label for="%1$s"><input type="checkbox" name="%1$s" id="%1$s" value="1" %2$s /> %3$s</label>',
		esc_attr( OPT_HARD_BLOCK ),
		checked( $wsec_hard_block, true, false ),
		esc_html__( 'Also reject disallowed express-checkout orders on the server', 'woo-stripe-express-by-country' )
	);

	printf(
		'<p class="description">%s</p>',
		esc_html__( 'Optional. The display gate above already hides the buttons. Enable this only if you need to guarantee that a disallowed order can never complete via express checkout — note it can reject a wallet payment after the customer has started it, because their wallet country may differ from the country shown.', 'woo-stripe-express-by-country' )
	);

	echo '</fieldset></td></tr>';
}

echo '</table>';

submit_button( __( 'Save Changes', 'woo-stripe-express-by-country' ) );

echo '</form>';
echo '</div>';
