<?php
defined( 'ABSPATH' ) || exit;

/**
 * [drift_gift_cards_balance_check] — drop this on the Gift Vouchers page so customers
 * can look up what's left on their card without ringing up.
 */
class Drift_Gift_Cards_Shortcode {

	public static function init() {
		add_shortcode( 'drift_gift_cards_balance_check', array( __CLASS__, 'render' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	public static function assets() {
		global $post;
		if ( ! $post || ! has_shortcode( $post->post_content, 'drift_gift_cards_balance_check' ) ) {
			return;
		}

		wp_enqueue_script(
			'drift-gift-cards-balance-check',
			DRIFT_GIFT_CARDS_URL . 'assets/js/balance-check.js',
			array( 'jquery' ),
			DRIFT_GIFT_CARDS_VERSION,
			true
		);
		wp_localize_script(
			'drift-gift-cards-balance-check',
			'driftGiftCardsBalance',
			array(
				'restUrl' => esc_url_raw( rest_url( 'drift-gift-cards/v1/balance' ) ),
				'i18n'    => array(
					'checking' => __( 'Checking…', 'drift-gift-cards' ),
					'error'    => __( 'Something went wrong. Please try again.', 'drift-gift-cards' ),
				),
			)
		);
		wp_enqueue_style( 'drift-gift-cards-checkout-block', DRIFT_GIFT_CARDS_URL . 'assets/css/gift-card.css', array(), DRIFT_GIFT_CARDS_VERSION );
	}

	public static function render() {
		ob_start();
		?>
		<div class="drift-gift-cards-balance-check">
			<form id="drift-gift-cards-balance-form">
				<label for="drift-gift-cards-balance-code"><?php esc_html_e( 'Gift card code', 'drift-gift-cards' ); ?></label>
				<div class="drift-gift-cards-field-row">
					<input type="text" id="drift-gift-cards-balance-code" placeholder="<?php esc_attr_e( 'e.g. GC-4F82-9X3K', 'drift-gift-cards' ); ?>" required />
					<button type="submit"><?php esc_html_e( 'Check balance', 'drift-gift-cards' ); ?></button>
				</div>
			</form>
			<p id="drift-gift-cards-balance-result"></p>
		</div>
		<?php
		return ob_get_clean();
	}
}
