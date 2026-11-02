<?php
/**
 * Gift card purchase fields, shown on the product page before Add to Cart.
 *
 * @var array  $presets
 * @var float  $min
 * @var float  $max
 * @var float  $hard_copy_fee
 * @var bool   $hard_copy_enabled
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="drift-gift-cards-purchase-fields">

	<?php if ( ! empty( $presets ) ) : ?>
		<p class="form-row form-row-wide">
			<label for="drift_gift_cards_amount"><?php esc_html_e( 'How much would you like to put on the gift card?', 'drift-gift-cards' ); ?> <span class="required">*</span></label>
			<select name="drift_gift_cards_amount" id="drift_gift_cards_amount" class="drift-gift-cards-amount-select" required>
				<option value=""><?php esc_html_e( 'Select an amount', 'drift-gift-cards' ); ?></option>
				<?php foreach ( $presets as $amount ) : ?>
					<option value="<?php echo esc_attr( $amount ); ?>">
						<?php echo wp_kses_post( wc_price( $amount ) ); ?>
					</option>
				<?php endforeach; ?>
				<option value="custom"><?php esc_html_e( 'Choose my own amount', 'drift-gift-cards' ); ?></option>
			</select>
		</p>
		<p class="form-row form-row-wide drift-gift-cards-custom-amount-row" style="display:none;">
			<label for="drift_gift_cards_amount_custom"><?php esc_html_e( 'Enter your own amount (£)', 'drift-gift-cards' ); ?></label>
			<input type="number" id="drift_gift_cards_amount_custom" step="0.01" min="<?php echo esc_attr( $min ); ?>" max="<?php echo esc_attr( $max ); ?>" placeholder="<?php echo esc_attr( $min . ' - ' . $max ); ?>" />
			<span class="description">
				<?php
				printf(
					/* translators: 1: minimum amount, 2: maximum amount */
					esc_html__( 'Must be between %1$s and %2$s.', 'drift-gift-cards' ),
					wp_kses_post( wc_price( $min ) ),
					wp_kses_post( wc_price( $max ) )
				);
				?>
			</span>
		</p>
	<?php else : ?>
		<p class="form-row form-row-wide">
			<label for="drift_gift_cards_amount"><?php esc_html_e( 'How much would you like to put on the gift card? (£)', 'drift-gift-cards' ); ?> <span class="required">*</span></label>
			<input type="number" name="drift_gift_cards_amount" id="drift_gift_cards_amount" step="0.01" min="<?php echo esc_attr( $min ); ?>" max="<?php echo esc_attr( $max ); ?>" required />
			<span class="description">
				<?php
				printf(
					/* translators: 1: minimum amount, 2: maximum amount */
					esc_html__( 'Must be between %1$s and %2$s.', 'drift-gift-cards' ),
					wp_kses_post( wc_price( $min ) ),
					wp_kses_post( wc_price( $max ) )
				);
				?>
			</span>
		</p>
	<?php endif; ?>

	<p class="form-row form-row-wide">
		<label for="drift_gift_cards_recipient_name"><?php esc_html_e( 'Who is this gift card for? (their name)', 'drift-gift-cards' ); ?></label>
		<input type="text" name="drift_gift_cards_recipient_name" id="drift_gift_cards_recipient_name" style="width:100%;" />
		<span class="description" style="display:block;"><?php esc_html_e( 'We\'ll use this to greet them in the gift card email, e.g. "Hi Jane,"', 'drift-gift-cards' ); ?></span>
	</p>

	<p class="form-row form-row-wide">
		<label for="drift_gift_cards_recipient_email"><?php esc_html_e( 'Their email address', 'drift-gift-cards' ); ?></label>
		<input type="email" name="drift_gift_cards_recipient_email" id="drift_gift_cards_recipient_email" style="width:100%;" />
		<span class="description" style="display:block;"><?php echo wp_kses_post( __( "We'll email the gift card straight to this address so they can use it themselves.<br/>Buying it for yourself, or want to send it on separately? Just leave this blank and we'll email it to you instead.", 'drift-gift-cards' ) ); ?></span>
	</p>

	<p class="form-row form-row-wide">
		<label for="drift_gift_cards_delivery_date"><?php esc_html_e( 'When should we send it?', 'drift-gift-cards' ); ?></label>
		<input type="date" name="drift_gift_cards_delivery_date" id="drift_gift_cards_delivery_date" min="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>" />
		<span class="description" style="display:block;"><?php esc_html_e( "Pick a future date if you'd like the gift card email to arrive on a specific day, like a birthday. Leave this blank and we'll send it out straight away once your order is complete.", 'drift-gift-cards' ); ?></span>
	</p>

	<p class="form-row form-row-wide">
		<label for="drift_gift_cards_message"><?php esc_html_e( 'Add a personal message (optional)', 'drift-gift-cards' ); ?></label>
		<textarea name="drift_gift_cards_message" id="drift_gift_cards_message" rows="3" maxlength="500"></textarea>
		<span class="description"><?php esc_html_e( "This message will be included in the gift card email, underneath the recipient's name.", 'drift-gift-cards' ); ?></span>
	</p>

	<?php if ( ! empty( $hard_copy_enabled ) ) : ?>

	<p class="form-row form-row-wide">
		<label for="drift_gift_cards_hard_copy">
			<input type="checkbox" name="drift_gift_cards_hard_copy" id="drift_gift_cards_hard_copy" value="yes" />
			<?php
			printf(
				/* translators: %s: printed card fee, e.g. £10.00 */
				esc_html__( 'Also send a printed gift card by post (+%s)', 'drift-gift-cards' ),
				wp_kses_post( wc_price( $hard_copy_fee ) )
			);
			?>
		</label>
		<span class="description" style="display:block;"><?php esc_html_e( "We'll post a printed card to the address below, as well as emailing the code.", 'drift-gift-cards' ); ?></span>
	</p>

	<div class="drift-gift-cards-hard-copy-address" style="display:none;">

		<p class="form-row form-row-wide">
			<label for="drift_gift_cards_ship_name"><?php esc_html_e( 'Recipient name', 'drift-gift-cards' ); ?></label>
			<input type="text" name="drift_gift_cards_ship_name" id="drift_gift_cards_ship_name" style="width:100%;" />
		</p>

		<p class="form-row form-row-wide">
			<label for="drift_gift_cards_ship_address_1"><?php esc_html_e( 'Address line 1', 'drift-gift-cards' ); ?></label>
			<input type="text" name="drift_gift_cards_ship_address_1" id="drift_gift_cards_ship_address_1" style="width:100%;" />
		</p>

		<p class="form-row form-row-wide">
			<label for="drift_gift_cards_ship_address_2"><?php esc_html_e( 'Address line 2 (optional)', 'drift-gift-cards' ); ?></label>
			<input type="text" name="drift_gift_cards_ship_address_2" id="drift_gift_cards_ship_address_2" style="width:100%;" />
		</p>

		<p class="form-row form-row-first">
			<label for="drift_gift_cards_ship_city"><?php esc_html_e( 'Town / City', 'drift-gift-cards' ); ?></label>
			<input type="text" name="drift_gift_cards_ship_city" id="drift_gift_cards_ship_city" style="width:100%;" />
		</p>

		<p class="form-row form-row-last">
			<label for="drift_gift_cards_ship_postcode"><?php esc_html_e( 'Postcode', 'drift-gift-cards' ); ?></label>
			<input type="text" name="drift_gift_cards_ship_postcode" id="drift_gift_cards_ship_postcode" style="width:100%;" />
		</p>

		<?php
		woocommerce_form_field(
			'drift_gift_cards_ship_country',
			array(
				'type'    => 'country',
				'label'   => __( 'Country', 'drift-gift-cards' ),
				'class'   => array( 'form-row-wide', 'drift-gift-cards-ship-country' ),
				'default' => WC()->countries ? WC()->countries->get_base_country() : 'GB',
			)
		);
		?>

	</div>

	<?php endif; ?>

</div>
