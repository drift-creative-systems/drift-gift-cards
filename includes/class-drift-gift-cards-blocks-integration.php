<?php
defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

/**
 * Registers the redeem-gift-card field into the Cart & Checkout blocks.
 *
 * The Blocks UI has no vanilla-JS extension point for the built-in slots —
 * @woocommerce/blocks-checkout's ExperimentalOrderMeta Slot is React-only —
 * so this script uses wp.element.createElement directly (no JSX, no build
 * step) rather than pulling in a full React toolchain for one small field.
 */
class Drift_Gift_Cards_Blocks_Integration implements IntegrationInterface {

	public function get_name() {
		return 'drift-gift-cards-gift-cards';
	}

	public function initialize() {
		$handle = 'drift-gift-cards-checkout-block';

		wp_register_script(
			$handle,
			DRIFT_GIFT_CARDS_URL . 'assets/js/checkout-block.js',
			array(
				'wc-blocks-checkout',
				'wp-element',
				'wp-plugins',
				'wp-i18n',
				'wp-data',
			),
			DRIFT_GIFT_CARDS_VERSION,
			true
		);

		wp_localize_script(
			$handle,
			'driftGiftCardsCheckout',
			array(
				'restUrl' => esc_url_raw( rest_url( 'drift-gift-cards/v1' ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'i18n'    => array(
					'label'       => __( 'Have a gift card?', 'drift-gift-cards' ),
					'placeholder' => __( 'Enter code', 'drift-gift-cards' ),
					'apply'       => __( 'Apply', 'drift-gift-cards' ),
					'remove'      => __( 'Remove', 'drift-gift-cards' ),
					'applying'    => __( 'Applying…', 'drift-gift-cards' ),
				),
			)
		);

		wp_enqueue_style(
			'drift-gift-cards-checkout-block',
			DRIFT_GIFT_CARDS_URL . 'assets/css/gift-card.css',
			array(),
			DRIFT_GIFT_CARDS_VERSION
		);
	}

	public function get_script_handles() {
		return array( 'drift-gift-cards-checkout-block' );
	}

	public function get_editor_script_handles() {
		return array();
	}

	public function get_script_data() {
		return array();
	}
}
