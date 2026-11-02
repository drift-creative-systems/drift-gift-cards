<?php
defined( 'ABSPATH' ) || exit;

/**
 * Gift Cards → Settings — the client's gift card image, the email intro
 * copy, and the default expiry applied to manually-created cards.
 */
class Drift_Gift_Cards_Settings {

	const OPTION_IMAGE_ID   = 'drift_gift_cards_gift_card_image_id';
	const OPTION_EMAIL_INTRO = 'drift_gift_cards_email_intro';
	const OPTION_EXPIRY_MONTHS = 'drift_gift_cards_default_expiry_months';
	const OPTION_REPORT_EMAILS = 'drift_gift_cards_report_emails';
	const OPTION_REPORT_FREQUENCIES = 'drift_gift_cards_report_frequencies';
	const OPTION_HARD_COPY_FEE = 'drift_gift_cards_hard_copy_fee';
	const OPTION_HARD_COPY_ENABLED = 'drift_gift_cards_hard_copy_enabled';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_post_drift_gift_cards_save_settings', array( __CLASS__, 'handle_save' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	public static function register_menu() {
		add_submenu_page(
			'edit.php?post_type=product',
			__( 'Gift Card Settings', 'drift-gift-cards' ),
			__( 'Gift Card Settings', 'drift-gift-cards' ),
			'manage_woocommerce',
			'drift-gift-cards-settings',
			array( __CLASS__, 'render_page' )
		);
	}

	public static function enqueue_assets( $hook ) {
		if ( 'product_page_drift-gift-cards-settings' !== $hook ) {
			return;
		}
		wp_enqueue_media();
		Drift_Gift_Cards_Admin_UI::enqueue();
		wp_enqueue_style( 'drift-gift-cards-admin', DRIFT_GIFT_CARDS_URL . 'assets/css/admin.css', array( Drift_Gift_Cards_Admin_UI::HANDLE ), DRIFT_GIFT_CARDS_VERSION );
		wp_enqueue_script( 'drift-gift-cards-admin', DRIFT_GIFT_CARDS_URL . 'assets/js/admin.js', array( 'jquery' ), DRIFT_GIFT_CARDS_VERSION, true );
		wp_localize_script(
			'drift-gift-cards-admin',
			'driftGiftCardsAdmin',
			array(
				'mediaTitle'  => __( 'Select gift card image', 'drift-gift-cards' ),
				'mediaButton' => __( 'Use this image', 'drift-gift-cards' ),
				'previewAlt'  => __( 'Gift card image preview', 'drift-gift-cards' ),
			)
		);
	}

	public static function get_image_id() {
		return absint( get_option( self::OPTION_IMAGE_ID, 0 ) );
	}

	public static function get_image_url( $size = 'large' ) {
		$image_id = self::get_image_id();
		if ( ! $image_id ) {
			return '';
		}
		$url = wp_get_attachment_image_url( $image_id, $size );
		return $url ? $url : '';
	}

	public static function get_email_intro() {
		$intro = get_option( self::OPTION_EMAIL_INTRO, '' );
		if ( '' === $intro ) {
			$intro = __( "You've been sent a gift card.", 'drift-gift-cards' );
		}
		return $intro;
	}

	public static function get_expiry_months() {
		return absint( get_option( self::OPTION_EXPIRY_MONTHS, 0 ) );
	}

	/**
	 * Fee charged for a printed/posted physical gift card, per card.
	 * Defaults to £10 — editable so it can be changed, or reused on
	 * another client site, without a code edit.
	 */
	public static function get_hard_copy_fee() {
		$fee = get_option( self::OPTION_HARD_COPY_FEE, '' );
		return '' === $fee ? 10.0 : (float) $fee;
	}

	/**
	 * Whether customers are offered the "send a printed card by post"
	 * option on the product page. Defaults to on, so sites that had the
	 * option before this setting existed keep it until it's switched off.
	 */
	public static function is_hard_copy_enabled() {
		return 'no' !== get_option( self::OPTION_HARD_COPY_ENABLED, 'yes' );
	}

	/**
	 * Stored as one address per line; returns only the ones that pass
	 * is_email() so a typo in one address doesn't block the rest.
	 */
	public static function get_report_emails() {
		$raw    = get_option( self::OPTION_REPORT_EMAILS, '' );
		$lines  = preg_split( '/[\r\n,]+/', $raw );
		$emails = array();

		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( $line && is_email( $line ) ) {
				$emails[] = $line;
			}
		}

		return array_unique( $emails );
	}

	public static function get_report_emails_raw() {
		return get_option( self::OPTION_REPORT_EMAILS, '' );
	}

	public static function get_report_frequencies() {
		$frequencies = get_option( self::OPTION_REPORT_FREQUENCIES, array() );
		if ( ! is_array( $frequencies ) ) {
			$frequencies = array();
		}
		return array_values( array_intersect( $frequencies, array( 'weekly', 'monthly' ) ) );
	}

	public static function render_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'drift-gift-cards' ) );
		}

		$image_id = self::get_image_id();
		$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '';
		?>
		<div class="wrap drift-ui drift-ui--narrow">
			<?php
			Drift_Gift_Cards_Admin_UI::header(
				__( 'Gift Card Settings', 'drift-gift-cards' ),
				__( 'The gift card email, default expiry for manually-created cards, printed card fee and sales reports.', 'drift-gift-cards' ),
				array(
					array(
						'label' => __( 'Gift card codes', 'drift-gift-cards' ),
						'url'   => admin_url( 'edit.php?post_type=product&page=drift-gift-cards' ),
					),
				)
			);
			?>

			<?php if ( isset( $_GET['updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'drift-gift-cards' ); ?></p></div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'drift_gift_cards_save_settings' ); ?>
				<input type="hidden" name="action" value="drift_gift_cards_save_settings" />

				<section class="drift-ui-card" aria-labelledby="drift-gift-cards-email-title">
					<h2 class="drift-ui-card__title" id="drift-gift-cards-email-title"><?php esc_html_e( 'Gift card email', 'drift-gift-cards' ); ?></h2>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Gift card image', 'drift-gift-cards' ); ?></th>
							<td>
								<input type="hidden" name="drift_gift_cards_gift_card_image_id" id="drift_gift_cards_gift_card_image_id" value="<?php echo esc_attr( $image_id ); ?>" />
								<div id="drift-gift-cards-image-preview" class="drift-gift-cards-image-preview">
									<?php if ( $image_url ) : ?>
										<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php esc_attr_e( 'Gift card image preview', 'drift-gift-cards' ); ?>" />
									<?php endif; ?>
								</div>
								<p class="drift-ui-actions">
									<button type="button" class="button" id="drift-gift-cards-upload-image"><?php esc_html_e( 'Select image', 'drift-gift-cards' ); ?></button>
									<button type="button" class="button" id="drift-gift-cards-remove-image"<?php echo $image_id ? '' : ' hidden'; ?>><?php esc_html_e( 'Remove', 'drift-gift-cards' ); ?></button>
								</p>
								<p class="description"><?php esc_html_e( 'Shown in the gift card email.', 'drift-gift-cards' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="drift_gift_cards_email_intro"><?php esc_html_e( 'Email intro text', 'drift-gift-cards' ); ?></label></th>
							<td>
								<textarea name="drift_gift_cards_email_intro" id="drift_gift_cards_email_intro" rows="3" class="large-text"><?php echo esc_textarea( get_option( self::OPTION_EMAIL_INTRO, '' ) ); ?></textarea>
							</td>
						</tr>
					</table>
				</section>

				<section class="drift-ui-card" aria-labelledby="drift-gift-cards-cards-title">
					<h2 class="drift-ui-card__title" id="drift-gift-cards-cards-title"><?php esc_html_e( 'Cards and pricing', 'drift-gift-cards' ); ?></h2>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="drift_gift_cards_default_expiry_months"><?php esc_html_e( 'Default expiry for manually-created cards (months)', 'drift-gift-cards' ); ?></label></th>
							<td>
								<input type="number" min="0" step="1" name="drift_gift_cards_default_expiry_months" id="drift_gift_cards_default_expiry_months" value="<?php echo esc_attr( self::get_expiry_months() ); ?>" />
								<p class="description"><?php esc_html_e( '0 = never expires. Only applies to cards created manually from the admin list — cards sold via a product use that product\'s own expiry setting.', 'drift-gift-cards' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Printed cards', 'drift-gift-cards' ); ?></th>
							<td>
								<label for="drift_gift_cards_hard_copy_enabled">
									<input type="checkbox" name="drift_gift_cards_hard_copy_enabled" id="drift_gift_cards_hard_copy_enabled" value="yes" <?php checked( self::is_hard_copy_enabled() ); ?> />
									<?php esc_html_e( 'Offer a printed gift card by post', 'drift-gift-cards' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Untick to hide the "send a printed gift card by post" option and postal address fields on gift card products.', 'drift-gift-cards' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="drift_gift_cards_hard_copy_fee"><?php esc_html_e( 'Printed card fee (£)', 'drift-gift-cards' ); ?></label></th>
							<td>
								<input type="number" min="0" step="0.01" name="drift_gift_cards_hard_copy_fee" id="drift_gift_cards_hard_copy_fee" value="<?php echo esc_attr( self::get_hard_copy_fee() ); ?>" />
								<p class="description"><?php esc_html_e( 'Charged per gift card when the customer chooses to have a printed card posted to the recipient, on top of the card\'s own value.', 'drift-gift-cards' ); ?></p>
							</td>
						</tr>
					</table>
				</section>

				<section class="drift-ui-card" aria-labelledby="drift-gift-cards-reports-title">
					<h2 class="drift-ui-card__title" id="drift-gift-cards-reports-title"><?php esc_html_e( 'Sales reports', 'drift-gift-cards' ); ?></h2>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="drift_gift_cards_report_emails"><?php esc_html_e( 'Sales report email addresses', 'drift-gift-cards' ); ?></label></th>
							<td>
								<textarea name="drift_gift_cards_report_emails" id="drift_gift_cards_report_emails" rows="3" class="large-text" placeholder="one@example.com&#10;two@example.com"><?php echo esc_textarea( self::get_report_emails_raw() ); ?></textarea>
								<p class="description"><?php esc_html_e( 'One address per line (or comma separated). Leave blank to disable. The report is sent to every valid address listed.', 'drift-gift-cards' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Sales report frequency', 'drift-gift-cards' ); ?></th>
							<td>
								<?php $frequencies = self::get_report_frequencies(); ?>
								<fieldset>
									<legend class="screen-reader-text"><?php esc_html_e( 'Sales report frequency', 'drift-gift-cards' ); ?></legend>
									<label>
										<input type="checkbox" name="drift_gift_cards_report_frequencies[]" value="weekly" <?php checked( in_array( 'weekly', $frequencies, true ) ); ?> />
										<?php esc_html_e( 'Weekly', 'drift-gift-cards' ); ?>
									</label>
									<label>
										<input type="checkbox" name="drift_gift_cards_report_frequencies[]" value="monthly" <?php checked( in_array( 'monthly', $frequencies, true ) ); ?> />
										<?php esc_html_e( 'Monthly', 'drift-gift-cards' ); ?>
									</label>
								</fieldset>
								<p class="description"><?php esc_html_e( 'Tick both to receive a weekly and a monthly report. A CSV of every gift card created in that period is emailed to the address(es) above.', 'drift-gift-cards' ); ?></p>
							</td>
						</tr>
					</table>
				</section>

				<?php submit_button( __( 'Save Settings', 'drift-gift-cards' ) ); ?>
			</form>
		</div>
		<?php
	}

	public static function handle_save() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'drift-gift-cards' ) );
		}
		check_admin_referer( 'drift_gift_cards_save_settings' );

		update_option( self::OPTION_IMAGE_ID, isset( $_POST['drift_gift_cards_gift_card_image_id'] ) ? absint( $_POST['drift_gift_cards_gift_card_image_id'] ) : 0 );
		update_option( self::OPTION_EMAIL_INTRO, isset( $_POST['drift_gift_cards_email_intro'] ) ? sanitize_textarea_field( wp_unslash( $_POST['drift_gift_cards_email_intro'] ) ) : '' );
		update_option( self::OPTION_EXPIRY_MONTHS, isset( $_POST['drift_gift_cards_default_expiry_months'] ) ? absint( $_POST['drift_gift_cards_default_expiry_months'] ) : 0 );
		update_option( self::OPTION_HARD_COPY_FEE, isset( $_POST['drift_gift_cards_hard_copy_fee'] ) ? wc_format_decimal( wp_unslash( $_POST['drift_gift_cards_hard_copy_fee'] ) ) : 10.0 );
		update_option( self::OPTION_HARD_COPY_ENABLED, isset( $_POST['drift_gift_cards_hard_copy_enabled'] ) ? 'yes' : 'no' );

		$report_emails = isset( $_POST['drift_gift_cards_report_emails'] ) ? sanitize_textarea_field( wp_unslash( $_POST['drift_gift_cards_report_emails'] ) ) : '';
		update_option( self::OPTION_REPORT_EMAILS, $report_emails );

		$report_frequencies = isset( $_POST['drift_gift_cards_report_frequencies'] ) ? (array) wp_unslash( $_POST['drift_gift_cards_report_frequencies'] ) : array();
		$report_frequencies = array_values( array_intersect( array_map( 'sanitize_text_field', $report_frequencies ), array( 'weekly', 'monthly' ) ) );
		update_option( self::OPTION_REPORT_FREQUENCIES, $report_frequencies );

		if ( class_exists( 'Drift_Gift_Cards_Report' ) ) {
			Drift_Gift_Cards_Report::reschedule();
		}

		wp_safe_redirect( add_query_arg( 'updated', '1', admin_url( 'edit.php?post_type=product&page=drift-gift-cards-settings' ) ) );
		exit;
	}
}
