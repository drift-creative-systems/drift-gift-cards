<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin screen to view gift cards, manually generate cards (e.g. for
 * someone who rings up wanting a voucher), and adjust balances/notes.
 * Sits alongside the Settings page under Products → Gift Cards.
 */
class Drift_Gift_Cards_Admin {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_post_drift_gift_cards_manual_create', array( $this, 'handle_manual_create' ) );
		add_action( 'admin_post_drift_gift_cards_adjust_status', array( $this, 'handle_adjust_status' ) );
		add_action( 'admin_post_drift_gift_cards_edit_balance', array( $this, 'handle_edit_balance' ) );
		add_action( 'admin_post_drift_gift_cards_redeem_card', array( $this, 'handle_redeem_card' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function enqueue_assets( $hook ) {
		if ( 'product_page_drift-gift-cards' !== $hook ) {
			return;
		}
		Drift_Gift_Cards_Admin_UI::enqueue();
		wp_enqueue_style( 'drift-gift-cards-admin', DRIFT_GIFT_CARDS_URL . 'assets/css/admin.css', array( Drift_Gift_Cards_Admin_UI::HANDLE ), DRIFT_GIFT_CARDS_VERSION );
		wp_enqueue_script( 'drift-gift-cards-admin', DRIFT_GIFT_CARDS_URL . 'assets/js/admin.js', array( 'jquery' ), DRIFT_GIFT_CARDS_VERSION, true );
		wp_localize_script(
			'drift-gift-cards-admin',
			'driftGiftCardsAdmin',
			array(
				'confirmDisable' => __( 'Disable this gift card?', 'drift-gift-cards' ),
			)
		);
	}

	public function register_menu() {
		add_submenu_page(
			'edit.php?post_type=product',
			__( 'Gift Cards', 'drift-gift-cards' ),
			__( 'Gift Card Codes', 'drift-gift-cards' ),
			'manage_woocommerce',
			'drift-gift-cards',
			array( $this, 'render_page' )
		);
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'drift-gift-cards' ) );
		}

		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$cards  = Drift_Gift_Cards_DB::get_cards( array( 'search' => $search, 'limit' => 100 ) );
		$view   = isset( $_GET['drift_gift_cards_view'] ) && 'create' === $_GET['drift_gift_cards_view'] ? 'create' : 'existing';
		?>
		<div class="wrap drift-ui">
			<?php
			Drift_Gift_Cards_Admin_UI::header(
				__( 'Gift Card Codes', 'drift-gift-cards' ),
				__( 'Look up, redeem, correct and disable gift cards, or create one for a phone or in-person sale.', 'drift-gift-cards' ),
				array(
					array(
						'label' => __( 'Settings', 'drift-gift-cards' ),
						'url'   => admin_url( 'edit.php?post_type=product&page=drift-gift-cards-settings' ),
					),
				)
			);
			?>

			<?php if ( isset( $_GET['drift_gift_cards_notice'] ) && 'balance_updated' === $_GET['drift_gift_cards_notice'] ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Balance updated.', 'drift-gift-cards' ); ?></p></div>
			<?php elseif ( isset( $_GET['drift_gift_cards_notice'] ) && 'redeemed' === $_GET['drift_gift_cards_notice'] ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Gift card redeemed.', 'drift-gift-cards' ); ?></p></div>
			<?php elseif ( isset( $_GET['drift_gift_cards_notice'] ) && 'created' === $_GET['drift_gift_cards_notice'] ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Gift card created.', 'drift-gift-cards' ); ?></p></div>
			<?php endif; ?>
			<?php if ( isset( $_GET['drift_gift_cards_error'] ) ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php echo esc_html( sanitize_text_field( wp_unslash( $_GET['drift_gift_cards_error'] ) ) ); ?></p></div>
			<?php endif; ?>

			<h2 class="nav-tab-wrapper">
				<a href="<?php echo esc_url( remove_query_arg( 'drift_gift_cards_view' ) ); ?>" class="nav-tab <?php echo 'existing' === $view ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Existing cards', 'drift-gift-cards' ); ?></a>
				<a href="<?php echo esc_url( add_query_arg( 'drift_gift_cards_view', 'create' ) ); ?>" class="nav-tab <?php echo 'create' === $view ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Create a card manually', 'drift-gift-cards' ); ?></a>
			</h2>

			<?php if ( 'create' === $view ) : ?>

				<div class="notice notice-info inline drift-gift-cards-guide">
					<p>
						<strong><?php esc_html_e( 'When to use this:', 'drift-gift-cards' ); ?></strong>
						<?php esc_html_e( "Use this form when someone wants a gift card without going through the website checkout — a phone order, or a customer paying in person with cash or card.", 'drift-gift-cards' ); ?>
					</p>
					<p>
						<?php esc_html_e( "1. Enter the amount to load onto the card and who it's for.", 'drift-gift-cards' ); ?><br />
						<?php esc_html_e( '2. Tick "Send email now" to email the card straight to them, or untick it if you\'d rather hand over the code yourself (e.g. printed or written down).', 'drift-gift-cards' ); ?><br />
						<?php esc_html_e( '3. Click "Create Gift Card". The new code will appear on the Existing Cards tab.', 'drift-gift-cards' ); ?>
					</p>
				</div>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'drift_gift_cards_manual_create' ); ?>
					<input type="hidden" name="action" value="drift_gift_cards_manual_create" />
					<section class="drift-ui-card" aria-labelledby="drift-gift-cards-create-title">
						<h2 class="drift-ui-card__title" id="drift-gift-cards-create-title"><?php esc_html_e( 'New gift card', 'drift-gift-cards' ); ?></h2>
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><label for="drift_gift_cards_amount"><?php esc_html_e( 'Amount (£)', 'drift-gift-cards' ); ?></label></th>
								<td><input type="number" step="0.01" min="1" max="1000" name="amount" id="drift_gift_cards_amount" required /></td>
							</tr>
							<tr>
								<th scope="row"><label for="drift_gift_cards_recipient_email"><?php esc_html_e( 'Recipient email', 'drift-gift-cards' ); ?></label></th>
								<td><input type="email" name="recipient_email" id="drift_gift_cards_recipient_email" required /></td>
							</tr>
							<tr>
								<th scope="row"><label for="drift_gift_cards_recipient_name"><?php esc_html_e( 'Recipient name', 'drift-gift-cards' ); ?></label></th>
								<td><input type="text" name="recipient_name" id="drift_gift_cards_recipient_name" /></td>
							</tr>
							<tr>
								<th scope="row"><label for="drift_gift_cards_send_email"><?php esc_html_e( 'Send email now?', 'drift-gift-cards' ); ?></label></th>
								<td><input type="checkbox" name="send_email" id="drift_gift_cards_send_email" value="yes" checked /></td>
							</tr>
						</table>
					</section>
					<?php submit_button( __( 'Create Gift Card', 'drift-gift-cards' ) ); ?>
				</form>

			<?php else : ?>

				<div class="notice notice-info inline drift-gift-cards-guide">
					<p><strong><?php esc_html_e( 'Quick guide:', 'drift-gift-cards' ); ?></strong></p>
					<p>
						<?php esc_html_e( 'Search by code or customer email using the box below.', 'drift-gift-cards' ); ?>
					</p>
					<p>
						<strong><?php esc_html_e( 'Redeem', 'drift-gift-cards' ); ?></strong> — <?php esc_html_e( 'a customer is spending some or all of the card in person or over the phone (not through the website checkout). Click Redeem, enter how much they\'re spending, and it comes off the balance.', 'drift-gift-cards' ); ?><br />
						<strong><?php esc_html_e( 'Edit', 'drift-gift-cards' ); ?></strong> — <?php esc_html_e( "the balance was entered wrong and needs correcting. Click Edit and type in the correct balance — this overwrites it directly, it doesn't add or subtract.", 'drift-gift-cards' ); ?><br />
						<strong><?php esc_html_e( 'Disable / Re-enable', 'drift-gift-cards' ); ?></strong> — <?php esc_html_e( 'Disable stops a card being used at all, e.g. if it\'s reported lost or fraudulent. Re-enable brings it back into use.', 'drift-gift-cards' ); ?>
					</p>
				</div>

				<section class="drift-ui-card" aria-labelledby="drift-gift-cards-list-title">
					<div class="drift-ui-card__head">
						<h2 class="drift-ui-card__title" id="drift-gift-cards-list-title"><?php esc_html_e( 'Existing cards', 'drift-gift-cards' ); ?></h2>
						<form method="get" class="drift-ui-actions" role="search">
							<input type="hidden" name="post_type" value="product" />
							<input type="hidden" name="page" value="drift-gift-cards" />
							<label for="drift-gift-cards-search" class="screen-reader-text"><?php esc_html_e( 'Search gift cards', 'drift-gift-cards' ); ?></label>
							<input type="search" id="drift-gift-cards-search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search code or email…', 'drift-gift-cards' ); ?>" />
							<?php submit_button( __( 'Search', 'drift-gift-cards' ), '', '', false ); ?>
						</form>
					</div>

				<div class="drift-gift-cards-table-wrap">
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Code', 'drift-gift-cards' ); ?></th>
							<th><?php esc_html_e( 'Recipient', 'drift-gift-cards' ); ?></th>
							<th><?php esc_html_e( 'Initial', 'drift-gift-cards' ); ?></th>
							<th><?php esc_html_e( 'Balance', 'drift-gift-cards' ); ?></th>
							<th><?php esc_html_e( 'Status', 'drift-gift-cards' ); ?></th>
							<th><?php esc_html_e( 'Expires', 'drift-gift-cards' ); ?></th>
							<th><?php esc_html_e( 'Order', 'drift-gift-cards' ); ?></th>
							<th><?php esc_html_e( 'Paid via', 'drift-gift-cards' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'drift-gift-cards' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $cards ) ) : ?>
							<tr><td colspan="9"><?php esc_html_e( 'No gift cards found.', 'drift-gift-cards' ); ?></td></tr>
						<?php else : ?>
							<?php foreach ( $cards as $card ) : ?>
								<tr>
									<td><code><?php echo esc_html( $card->code ); ?></code></td>
									<td><?php echo esc_html( $card->recipient_email ); ?></td>
									<td><?php echo wp_kses_post( wc_price( $card->initial_amount ) ); ?></td>
									<td><?php echo wp_kses_post( wc_price( $card->balance ) ); ?></td>
									<td><span class="drift-ui-badge <?php echo esc_attr( 'active' === $card->status ? 'drift-ui-badge--success' : 'drift-ui-badge--error' ); ?>"><?php echo esc_html( ucfirst( $card->status ) ); ?></span></td>
									<td><?php echo $card->expires_at ? esc_html( date_i18n( 'j M Y', strtotime( $card->expires_at ) ) ) : esc_html__( 'Never', 'drift-gift-cards' ); ?></td>
									<td><?php echo $card->order_id ? esc_html( '#' . $card->order_id ) : '—'; ?></td>
									<td><?php echo esc_html( self::payment_method_label( $card ) ); ?></td>
									<td>
										<?php if ( 'active' === $card->status && $card->balance > 0 ) : ?>
											<a href="#" class="drift-gift-cards-toggle-redeem" data-code="<?php echo esc_attr( $card->code ); ?>"><?php esc_html_e( 'Redeem', 'drift-gift-cards' ); ?></a> |
										<?php endif; ?>
										<a href="#" class="drift-gift-cards-toggle-edit" data-code="<?php echo esc_attr( $card->code ); ?>"><?php esc_html_e( 'Edit', 'drift-gift-cards' ); ?></a> |
										<?php if ( 'active' === $card->status ) : ?>
											<a href="<?php echo esc_url( $this->status_action_url( $card->code, 'disabled' ) ); ?>" class="drift-gift-cards-confirm-disable"><?php esc_html_e( 'Disable', 'drift-gift-cards' ); ?></a>
										<?php else : ?>
											<a href="<?php echo esc_url( $this->status_action_url( $card->code, 'active' ) ); ?>"><?php esc_html_e( 'Re-enable', 'drift-gift-cards' ); ?></a>
										<?php endif; ?>
									</td>
								</tr>
								<?php if ( 'active' === $card->status && $card->balance > 0 ) : ?>
									<tr class="drift-gift-cards-redeem-row" data-code="<?php echo esc_attr( $card->code ); ?>" hidden>
										<td colspan="9">
											<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
												<?php wp_nonce_field( 'drift_gift_cards_redeem_card_' . $card->code ); ?>
												<input type="hidden" name="action" value="drift_gift_cards_redeem_card" />
												<input type="hidden" name="code" value="<?php echo esc_attr( $card->code ); ?>" />
												<label>
													<?php esc_html_e( 'Amount to redeem (£)', 'drift-gift-cards' ); ?>
													<input type="number" step="0.01" min="0.01" max="<?php echo esc_attr( $card->balance ); ?>" name="redeem_amount" required />
												</label>
												<?php submit_button( __( 'Redeem', 'drift-gift-cards' ), 'secondary small', '', false ); ?>
											</form>
										</td>
									</tr>
								<?php endif; ?>
								<tr class="drift-gift-cards-edit-row" data-code="<?php echo esc_attr( $card->code ); ?>" hidden>
									<td colspan="9">
										<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
											<?php wp_nonce_field( 'drift_gift_cards_edit_balance_' . $card->code ); ?>
											<input type="hidden" name="action" value="drift_gift_cards_edit_balance" />
											<input type="hidden" name="code" value="<?php echo esc_attr( $card->code ); ?>" />
											<label>
												<?php esc_html_e( 'New balance (£)', 'drift-gift-cards' ); ?>
												<input type="number" step="0.01" min="0" name="balance" value="<?php echo esc_attr( $card->balance ); ?>" required />
											</label>
											<?php submit_button( __( 'Save balance', 'drift-gift-cards' ), 'secondary small', '', false ); ?>
										</form>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
				</div>
				</section>

			<?php endif; ?>

		</div>
		<?php
	}

	/**
	 * "Cash" for manually-created cards (no order — phone/in-person sale,
	 * see handle_manual_create()); otherwise the order's actual payment
	 * method title (e.g. "Credit Card (Stripe)") so the two ways a card
	 * gets funded are distinguishable at a glance.
	 */
	public static function payment_method_label( $card ) {
		if ( ! $card->order_id ) {
			return __( 'Cash (manual)', 'drift-gift-cards' );
		}

		$order = wc_get_order( $card->order_id );
		if ( ! $order ) {
			return __( 'Unknown', 'drift-gift-cards' );
		}

		$title = $order->get_payment_method_title();

		return $title ? $title : __( 'Unknown', 'drift-gift-cards' );
	}

	private function status_action_url( $code, $status ) {
		return wp_nonce_url(
			add_query_arg(
				array(
					'action' => 'drift_gift_cards_adjust_status',
					'code'   => $code,
					'status' => $status,
				),
				admin_url( 'admin-post.php' )
			),
			'drift_gift_cards_adjust_status_' . $code
		);
	}

	public function handle_manual_create() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'drift-gift-cards' ) );
		}
		check_admin_referer( 'drift_gift_cards_manual_create' );

		$amount          = isset( $_POST['amount'] ) ? wc_format_decimal( wp_unslash( $_POST['amount'] ) ) : 0;
		$recipient_email = isset( $_POST['recipient_email'] ) ? sanitize_email( wp_unslash( $_POST['recipient_email'] ) ) : '';
		$recipient_name  = isset( $_POST['recipient_name'] ) ? sanitize_text_field( wp_unslash( $_POST['recipient_name'] ) ) : '';
		$send_email      = isset( $_POST['send_email'] );

		if ( $amount > 0 && $amount <= 1000 && is_email( $recipient_email ) ) {
			$expiry_months = Drift_Gift_Cards_Settings::get_expiry_months();
			$expires_at    = $expiry_months > 0 ? gmdate( 'Y-m-d H:i:s', strtotime( "+{$expiry_months} months" ) ) : null;

			$code = Drift_Gift_Cards_DB::create_card(
				array(
					'initial_amount'  => $amount,
					'recipient_email' => $recipient_email,
					'recipient_name'  => $recipient_name,
					'expires_at'      => $expires_at,
				)
			);

			if ( is_wp_error( $code ) ) {
				$args = array(
					'drift_gift_cards_error' => rawurlencode( $code->get_error_message() ),
					'drift_gift_cards_view'  => 'create',
				);
			} else {
				if ( $send_email ) {
					$card = Drift_Gift_Cards_DB::get_card_by_code( $code );
					do_action( 'drift_gift_cards_send_gift_card_email', $card );
				}
				$args = array( 'drift_gift_cards_notice' => 'created' );
			}
		} else {
			$args = array(
				'drift_gift_cards_error' => rawurlencode( __( 'Enter a valid amount (£1–£1000) and recipient email.', 'drift-gift-cards' ) ),
				'drift_gift_cards_view'  => 'create',
			);
		}

		wp_safe_redirect( add_query_arg( $args, admin_url( 'edit.php?post_type=product&page=drift-gift-cards' ) ) );
		exit;
	}

	public function handle_adjust_status() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'drift-gift-cards' ) );
		}

		$code   = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';
		$status = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';

		check_admin_referer( 'drift_gift_cards_adjust_status_' . $code );

		if ( $code && in_array( $status, array( 'active', 'disabled' ), true ) ) {
			Drift_Gift_Cards_DB::set_status( $code, $status );
		}

		wp_safe_redirect( admin_url( 'edit.php?post_type=product&page=drift-gift-cards' ) );
		exit;
	}

	public function handle_edit_balance() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'drift-gift-cards' ) );
		}

		$code = isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : '';

		check_admin_referer( 'drift_gift_cards_edit_balance_' . $code );

		$balance = isset( $_POST['balance'] ) ? wc_format_decimal( wp_unslash( $_POST['balance'] ) ) : null;

		if ( $code && null !== $balance && '' !== $balance && (float) $balance >= 0 ) {
			$result = Drift_Gift_Cards_DB::set_balance( $code, $balance );
			$args   = is_wp_error( $result )
				? array( 'drift_gift_cards_error' => rawurlencode( $result->get_error_message() ) )
				: array( 'drift_gift_cards_notice' => 'balance_updated' );
		} else {
			$args = array( 'drift_gift_cards_error' => rawurlencode( __( 'Enter a valid balance.', 'drift-gift-cards' ) ) );
		}

		wp_safe_redirect( add_query_arg( $args, admin_url( 'edit.php?post_type=product&page=drift-gift-cards' ) ) );
		exit;
	}

	public function handle_redeem_card() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'drift-gift-cards' ) );
		}

		$code = isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : '';

		check_admin_referer( 'drift_gift_cards_redeem_card_' . $code );

		$amount = isset( $_POST['redeem_amount'] ) ? wc_format_decimal( wp_unslash( $_POST['redeem_amount'] ) ) : 0;

		if ( $code && $amount > 0 ) {
			$result = Drift_Gift_Cards_DB::adjust_balance( $code, -$amount, 'manual in-person/phone redemption' );
			$args   = is_wp_error( $result )
				? array( 'drift_gift_cards_error' => rawurlencode( $result->get_error_message() ) )
				: array( 'drift_gift_cards_notice' => 'redeemed' );
		} else {
			$args = array( 'drift_gift_cards_error' => rawurlencode( __( 'Enter a valid redemption amount.', 'drift-gift-cards' ) ) );
		}

		wp_safe_redirect( add_query_arg( $args, admin_url( 'edit.php?post_type=product&page=drift-gift-cards' ) ) );
		exit;
	}
}
