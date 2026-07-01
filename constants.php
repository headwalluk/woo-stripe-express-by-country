<?php
/**
 * Plugin-scope constants.
 *
 * All option keys, defaults, mode values, and magic strings live here so nothing
 * is hardcoded elsewhere in the plugin.
 *
 * @package WooStripeExpressByCountry
 */

namespace Woo_Stripe_Express_By_Country;

defined( 'ABSPATH' ) || die();

// ============================================================================
// Admin & Settings
// ============================================================================

const SETTINGS_PAGE_SLUG = 'woo-stripe-express-by-country';

// ============================================================================
// Feature Flags
// ============================================================================

/**
 * Whether the server-side hard block feature is available.
 *
 * Deferred to a post-1.0.0 release. While false, the hard-block setting is hidden
 * from the settings page, its value is not saved, and the checkout guards are
 * never registered — so the plugin ships as display-gate-only. Flip to true to
 * re-enable the feature (see dev-notes/00-project-tracker.md).
 */
const IS_SERVER_HARDBLOCK_AVAILABLE = false;

// ============================================================================
// Restriction Modes
// ============================================================================

/**
 * Allow-list: express checkout is shown ONLY to the configured countries.
 */
const MODE_ALLOW = 'allow';

/**
 * Block-list: express checkout is shown to everyone EXCEPT the configured countries.
 */
const MODE_BLOCK = 'block';

// ============================================================================
// Options - prefix with OPT_
// ============================================================================

/**
 * The active restriction mode. One of MODE_ALLOW or MODE_BLOCK.
 */
const OPT_MODE = 'wsec_mode';

/**
 * The configured country list (array of ISO 3166-1 alpha-2 codes).
 */
const OPT_COUNTRIES = 'wsec_countries';

/**
 * Whether to also enforce the restriction server-side (the "hard block").
 *
 * When enabled, a disallowed express-checkout order is rejected at checkout
 * validation even if the buttons were shown. Off by default: the display gate is
 * the primary, best-UX mechanism, and a hard block can reject a wallet order
 * mid-flow (the customer's wallet country may differ from what was shown).
 */
const OPT_HARD_BLOCK = 'wsec_hard_block';

// ============================================================================
// Defaults - prefix with DEF_
// ============================================================================

/**
 * Default mode on first install.
 *
 * Allow-list with an empty country list means express checkout is hidden from
 * everyone until the operator configures it — a safe, fail-closed default.
 */
const DEF_MODE = MODE_ALLOW;

// ============================================================================
// GitHub Updater
// ============================================================================

/**
 * The GitHub repository (owner/name) that serves plugin release zips.
 */
const UPDATER_GITHUB_REPO = 'headwalluk/woo-stripe-express-by-country';

/**
 * Transient key for the cached latest-release lookup.
 */
const UPDATER_CACHE_KEY = 'wsec_updater_latest_release';

/**
 * How long to cache the latest-release lookup, in seconds.
 */
const UPDATER_CACHE_TTL = 6 * HOUR_IN_SECONDS;

// ============================================================================
// Country Resolution
// ============================================================================

/**
 * The customer address field used to determine the country: 'shipping' or 'billing'.
 *
 * Shipping is used with a fallback to billing when the shipping country is empty.
 * This is an internal constant, not a UI setting.
 */
const COUNTRY_BASIS = 'shipping';
