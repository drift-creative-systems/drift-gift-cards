<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Weekly and/or monthly gift card sales report — a CSV of every card
 * created in the period, emailed to every address set on the Gift Card
 * Settings page. Weekly and monthly run as independent cron hooks so both
 * can be enabled at once. Driven by WP-Cron; if the site's cron is
 * disabled/offloaded, the scheduled events still need firing by whatever
 * real cron runner is in use (see README), same caveat as the scheduled
 * gift card delivery in Drift_Gift_Cards_Order.
 */
class Drift_Gift_Cards_Report {

	const CRON_HOOK_WEEKLY  = 'drift_gift_cards_send_sales_report_weekly';
	const CRON_HOOK_MONTHLY = 'drift_gift_cards_send_sales_report_monthly';

	public static function init() {
		add_filter( 'cron_schedules', array( __CLASS__, 'register_schedules' ) ); // phpcs:ignore WordPress.WP.CronInterval.CronSchedulesInterval
		add_action( self::CRON_HOOK_WEEKLY, array( __CLASS__, 'send_weekly_report' ) );
		add_action( self::CRON_HOOK_MONTHLY, array( __CLASS__, 'send_monthly_report' ) );
	}

	public static function register_schedules( $schedules ) {
		$schedules['drift_gift_cards_weekly']  = array(
			'interval' => WEEK_IN_SECONDS,
			'display'  => __( 'Once Weekly (Gift Card Report)', 'drift-gift-cards' ),
		);
		$schedules['drift_gift_cards_monthly'] = array(
			'interval' => 30 * DAY_IN_SECONDS,
			'display'  => __( 'Once Monthly (Gift Card Report)', 'drift-gift-cards' ),
		);
		return $schedules;
	}

	/**
	 * Brings both scheduled cron events in line with current settings.
	 * Called after Settings are saved; also safe to call any time.
	 */
	public static function reschedule() {
		$emails      = Drift_Gift_Cards_Settings::get_report_emails();
		$frequencies = Drift_Gift_Cards_Settings::get_report_frequencies();

		self::sync_schedule( self::CRON_HOOK_WEEKLY, 'drift_gift_cards_weekly', in_array( 'weekly', $frequencies, true ) && ! empty( $emails ) );
		self::sync_schedule( self::CRON_HOOK_MONTHLY, 'drift_gift_cards_monthly', in_array( 'monthly', $frequencies, true ) && ! empty( $emails ) );
	}

	private static function sync_schedule( $hook, $recurrence, $should_be_scheduled ) {
		$is_scheduled = false !== wp_next_scheduled( $hook );

		if ( $should_be_scheduled && ! $is_scheduled ) {
			wp_schedule_event( time(), $recurrence, $hook );
		} elseif ( ! $should_be_scheduled && $is_scheduled ) {
			wp_clear_scheduled_hook( $hook );
		}
	}

	public static function send_weekly_report() {
		self::send_report( 'weekly' );
	}

	public static function send_monthly_report() {
		self::send_report( 'monthly' );
	}

	private static function send_report( $frequency ) {
		$emails = Drift_Gift_Cards_Settings::get_report_emails();
		if ( empty( $emails ) ) {
			return;
		}

		$days  = 'monthly' === $frequency ? 30 : 7;
		$since = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		$cards  = Drift_Gift_Cards_DB::get_cards_since( $since );
		$totals = self::calculate_totals( $cards );
		$file   = self::build_csv( $cards, $totals );

		if ( ! $file ) {
			error_log( 'Drift Gift Cards: could not build gift card sales report CSV' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
			return;
		}

		$subject = sprintf(
			/* translators: %s: site name */
			__( 'Gift card sales report — %s', 'drift-gift-cards' ),
			get_bloginfo( 'name' )
		);
		$body    = sprintf(
			/* translators: 1: number of gift cards, 2: number of days covered, 3: total sold, 4: total redeemed */
			__( "%1\$d gift card(s) created in the last %2\$d days.\nTotal sold: %3\$s\nRedeemed so far (from those cards): %4\$s\nCSV attached.", 'drift-gift-cards' ),
			count( $cards ),
			$days,
			wp_strip_all_tags( wc_price( $totals['sold'] ) ),
			wp_strip_all_tags( wc_price( $totals['redeemed'] ) )
		);

		$sent = wp_mail( $emails, $subject, $body, array(), array( $file ) );

		if ( ! $sent ) {
			error_log( 'Drift Gift Cards: failed to send gift card sales report to ' . implode( ', ', $emails ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
		}

		wp_delete_file( $file );
	}

	/**
	 * Sold/redeemed against only the cards created in this report's window
	 * — not all redemption activity site-wide in that window, since a
	 * redemption timestamp isn't tracked separately from the card itself.
	 */
	private static function calculate_totals( array $cards ) {
		$sold     = 0.0;
		$redeemed = 0.0;

		foreach ( $cards as $card ) {
			$sold     += (float) $card->initial_amount;
			$redeemed += (float) $card->initial_amount - (float) $card->balance;
		}

		return array(
			'sold'     => round( $sold, 2 ),
			'redeemed' => round( $redeemed, 2 ),
		);
	}

	private static function build_csv( array $cards, array $totals ) {
		$file = wp_tempnam( 'drift-gift-cards-sales-report' );
		if ( ! $file ) {
			return false;
		}

		$handle = fopen( $file, 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( ! $handle ) {
			return false;
		}

		fputcsv( $handle, array( __( 'Total sold', 'drift-gift-cards' ), $totals['sold'] ) );
		fputcsv( $handle, array( __( 'Total redeemed', 'drift-gift-cards' ), $totals['redeemed'] ) );
		fputcsv( $handle, array( __( 'Outstanding balance', 'drift-gift-cards' ), round( $totals['sold'] - $totals['redeemed'], 2 ) ) );
		fputcsv( $handle, array() );

		fputcsv(
			$handle,
			array(
				__( 'Code', 'drift-gift-cards' ),
				__( 'Status', 'drift-gift-cards' ),
				__( 'Initial Amount', 'drift-gift-cards' ),
				__( 'Balance', 'drift-gift-cards' ),
				__( 'Recipient Name', 'drift-gift-cards' ),
				__( 'Recipient Email', 'drift-gift-cards' ),
				__( 'Purchaser Email', 'drift-gift-cards' ),
				__( 'Message', 'drift-gift-cards' ),
				__( 'Order ID', 'drift-gift-cards' ),
				__( 'Paid Via', 'drift-gift-cards' ),
				__( 'Expires At', 'drift-gift-cards' ),
				__( 'Created At', 'drift-gift-cards' ),
				__( 'Updated At', 'drift-gift-cards' ),
			)
		);

		foreach ( $cards as $card ) {
			fputcsv(
				$handle,
				array(
					$card->code,
					$card->status,
					$card->initial_amount,
					$card->balance,
					$card->recipient_name,
					$card->recipient_email,
					$card->purchaser_email,
					$card->message,
					$card->order_id,
					Drift_Gift_Cards_Admin::payment_method_label( $card ),
					$card->expires_at,
					$card->created_at,
					$card->updated_at,
				)
			);
		}

		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		return $file;
	}
}
