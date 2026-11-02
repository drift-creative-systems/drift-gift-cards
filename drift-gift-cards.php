<?php
/**
 * Plugin Name:       Drift: Gift Cards
 * Plugin URI:        https://driftcreativesystems.co.uk/
 * Update URI:        https://github.com/drift-creative-systems/drift-gift-cards
 * Description:       Simple WooCommerce gift card system — sell, email, redeem and check balance on gift cards. Built for WooCommerce Blocks Cart/Checkout with a configurable gift card image.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * Author:            Drift Creative Systems
 * Author URI:        https://driftcreativesystems.co.uk/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       drift-gift-cards
 *
 * Copyright © 2026 Drift Creative Systems Ltd
 *
 * @package Drift_Gift_Cards
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
|--------------------------------------------------------------------------
| Duplicate install guard
|--------------------------------------------------------------------------
| A second copy (e.g. a GitHub "Source code" zip unpacked alongside the real
| folder) would fatal on redeclared functions. Bail and tell the admin instead.
*/
if ( defined( 'DRIFT_GIFT_CARDS_VERSION' ) ) {
	add_action(
		'admin_notices',
		function () {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Drift: Gift Cards is installed more than once. Please delete the duplicate plugin folder.', 'drift-gift-cards' ) . '</p></div>';
		}
	);
	return;
}

define( 'DRIFT_GIFT_CARDS_PRODUCT_NAME', 'Drift: Gift Cards' );
define( 'DRIFT_GIFT_CARDS_VERSION', '1.0.0' );
define( 'DRIFT_GIFT_CARDS_FILE', __FILE__ );
define( 'DRIFT_GIFT_CARDS_PATH', plugin_dir_path( __FILE__ ) );
define( 'DRIFT_GIFT_CARDS_URL', plugin_dir_url( __FILE__ ) );

/*
|--------------------------------------------------------------------------
| Plugin Update Checker (via Composer)
|--------------------------------------------------------------------------
| vendor/ is committed. composer.json sets a fixed autoloader suffix so this
| plugin's Composer autoloader never clashes with another plugin's. The repo
| is public, so no token is needed: sites pick up each GitHub release's
| drift-gift-cards.zip (built by .github/workflows/release.yml).
*/
require_once DRIFT_GIFT_CARDS_PATH . 'vendor/autoload.php';

$drift_gift_cards_update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
	'https://github.com/drift-creative-systems/drift-gift-cards',
	__FILE__,
	'drift-gift-cards',
	6
);

$drift_gift_cards_update_checker->setBranch( 'main' );
$drift_gift_cards_update_checker->getVcsApi()->enableReleaseAssets();

function drift_gift_cards_missing_woocommerce_notice() {
	echo '<div class="notice notice-error"><p>' .
		esc_html__( 'Drift: Gift Cards requires WooCommerce to be installed and active.', 'drift-gift-cards' ) .
		'</p></div>';
}

add_action( 'plugins_loaded', 'drift_gift_cards_init' );

function drift_gift_cards_init() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'drift_gift_cards_missing_woocommerce_notice' );
		return;
	}

	require_once DRIFT_GIFT_CARDS_PATH . 'includes/class-drift-gift-cards-db.php';
	require_once DRIFT_GIFT_CARDS_PATH . 'includes/class-drift-gift-cards-admin-ui.php';
	require_once DRIFT_GIFT_CARDS_PATH . 'includes/class-drift-gift-cards-settings.php';
	require_once DRIFT_GIFT_CARDS_PATH . 'includes/class-drift-gift-cards-product.php';
	require_once DRIFT_GIFT_CARDS_PATH . 'includes/class-drift-gift-cards-order.php';
	require_once DRIFT_GIFT_CARDS_PATH . 'includes/class-drift-gift-cards-cart.php';
	require_once DRIFT_GIFT_CARDS_PATH . 'includes/class-drift-gift-cards-shortcode.php';
	require_once DRIFT_GIFT_CARDS_PATH . 'includes/class-drift-gift-cards-admin.php';
	require_once DRIFT_GIFT_CARDS_PATH . 'includes/class-drift-gift-cards-rest.php';
	require_once DRIFT_GIFT_CARDS_PATH . 'includes/class-drift-gift-cards-stripe.php';
	require_once DRIFT_GIFT_CARDS_PATH . 'includes/class-drift-gift-cards-report.php';

	Drift_Gift_Cards_Settings::init();
	Drift_Gift_Cards_Product::init();
	Drift_Gift_Cards_Order::init();
	Drift_Gift_Cards_Cart::instance();
	Drift_Gift_Cards_Shortcode::init();
	Drift_Gift_Cards_Admin::instance();
	Drift_Gift_Cards_REST::init();
	Drift_Gift_Cards_Stripe::init();
	Drift_Gift_Cards_Report::init();

	// Self-heals the cron schedule if the stored event was ever cleared
	// (site migration, cron table wiped) without needing a settings re-save.
	add_action( 'admin_init', array( 'Drift_Gift_Cards_Report', 'reschedule' ) );

	add_filter( 'woocommerce_email_classes', 'drift_gift_cards_register_email_class' );
}

function drift_gift_cards_register_email_class( $email_classes ) {
	// WC_Email is only guaranteed to exist once WC_Emails::init() has
	// started running (it includes its own base class right before firing
	// this filter), so this file is required here rather than eagerly in
	// drift_gift_cards_init() — WooCommerce's email system can load long after
	// 'plugins_loaded', e.g. on an admin page's asset check.
	require_once DRIFT_GIFT_CARDS_PATH . 'includes/class-drift-gift-cards-email.php';

	if ( class_exists( 'Drift_Gift_Cards_Email_Gift_Card' ) ) {
		$email_classes['Drift_Gift_Cards_Email_Gift_Card'] = new Drift_Gift_Cards_Email_Gift_Card();
	}

	return $email_classes;
}

register_activation_hook( __FILE__, 'drift_gift_cards_activate' );

function drift_gift_cards_activate() {
	require_once DRIFT_GIFT_CARDS_PATH . 'includes/class-drift-gift-cards-db.php';
	Drift_Gift_Cards_DB::create_table();
}

register_deactivation_hook( __FILE__, 'drift_gift_cards_deactivate' );

function drift_gift_cards_deactivate() {
	wp_clear_scheduled_hook( 'drift_gift_cards_send_sales_report_weekly' );
	wp_clear_scheduled_hook( 'drift_gift_cards_send_sales_report_monthly' );
}

add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				__FILE__,
				true
			);
		}
	}
);

/**
 * The "Have a gift card?" field in the Blocks Cart/Checkout sidebar
 * requires registering against WooCommerce Blocks' own integration
 * registry — it's a separate hook from the general plugin bootstrap
 * above because Blocks may load after 'plugins_loaded'.
 */
add_action(
	'woocommerce_blocks_loaded',
	function () {
		if ( ! class_exists( 'WooCommerce' ) || ! interface_exists( '\Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface' ) ) {
			return;
		}

		require_once DRIFT_GIFT_CARDS_PATH . 'includes/class-drift-gift-cards-blocks-integration.php';

		add_action(
			'woocommerce_blocks_cart_block_registration',
			function ( $integration_registry ) {
				$integration_registry->register( new Drift_Gift_Cards_Blocks_Integration() );
			}
		);
		add_action(
			'woocommerce_blocks_checkout_block_registration',
			function ( $integration_registry ) {
				$integration_registry->register( new Drift_Gift_Cards_Blocks_Integration() );
			}
		);
	}
);
