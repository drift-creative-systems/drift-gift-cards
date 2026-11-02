<?php
/**
 * Drift admin UI: product header, footer and stylesheet for this plugin's
 * screens.
 *
 * Markup and CSS follow the Drift admin design system
 * (assets/css/drift-admin-ui.css, copied from Drift: Redline) with the
 * Gift Cards emerald accent. Self-contained: nothing here depends on
 * another Drift plugin.
 *
 * @package Drift_Gift_Cards
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renders the Drift header and loads the design-system stylesheet.
 */
class Drift_Gift_Cards_Admin_UI {

	const REPO    = 'https://github.com/drift-creative-systems/drift-gift-cards';
	const WEBSITE = 'https://driftcreativesystems.co.uk/';
	const HANDLE  = 'drift-gift-cards-admin-ui';

	/**
	 * Enqueue the design-system stylesheet and the copyright footer. Call
	 * from admin_enqueue_scripts on this plugin's screens only.
	 */
	public static function enqueue() {
		wp_enqueue_style( self::HANDLE, DRIFT_GIFT_CARDS_URL . 'assets/css/drift-admin-ui.css', array(), DRIFT_GIFT_CARDS_VERSION );
		add_filter( 'admin_footer_text', array( __CLASS__, 'footer_text' ) );
	}

	/**
	 * Copyright and company details in the admin footer. Only hooked on
	 * this plugin's screens (see enqueue()).
	 *
	 * @return string
	 */
	public static function footer_text() {
		return 'Copyright &copy; ' . esc_html( wp_date( 'Y' ) ) . ' Drift Creative Systems Ltd &middot; Registered in England and Wales, company number 17548220. Registered office: 167-169 Great Portland Street, 5th Floor, London W1W 5PF';
	}

	/**
	 * Print the page header, followed by the marker core uses to place
	 * admin notices, so notices sit below the header rather than above it.
	 *
	 * @param string $title Page title (plain text).
	 * @param string $lead  Short description. May contain links, code and emphasis.
	 * @param array  $links Extra links shown before the standard ones: each array( 'label' => '', 'url' => '' ).
	 */
	public static function header( $title, $lead = '', $links = array() ) {
		$links = array_merge(
			$links,
			array(
				array(
					'label'    => __( 'GitHub', 'drift-gift-cards' ),
					'url'      => self::REPO,
					'external' => true,
				),
				array(
					'label'    => __( 'Changelog', 'drift-gift-cards' ),
					'url'      => self::REPO . '/releases',
					'external' => true,
				),
				array(
					'label'    => __( 'Drift Creative Systems', 'drift-gift-cards' ),
					'url'      => self::WEBSITE,
					'external' => true,
				),
			)
		);
		?>
		<header class="drift-ui-header">
			<div class="drift-ui-header__main">
				<?php self::product_mark(); ?>
				<h1 class="drift-ui-header__title"><?php echo esc_html( $title ); ?></h1>
				<?php if ( '' !== $lead ) : ?>
					<p class="drift-ui-header__lead"><?php echo wp_kses( $lead, self::lead_tags() ); ?></p>
				<?php endif; ?>
				<ul class="drift-ui-header__links">
					<?php foreach ( $links as $link ) : ?>
						<li>
							<?php if ( ! empty( $link['external'] ) ) : ?>
								<a href="<?php echo esc_url( $link['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $link['label'] ); ?><span class="screen-reader-text"> <?php esc_html_e( '(opens in a new tab)', 'drift-gift-cards' ); ?></span></a>
							<?php else : ?>
								<a href="<?php echo esc_url( $link['url'] ); ?>"><?php echo esc_html( $link['label'] ); ?></a>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<div class="drift-ui-header__meta">
				<span class="drift-ui-version">v<?php echo esc_html( DRIFT_GIFT_CARDS_VERSION ); ?></span>
			</div>
		</header>
		<hr class="wp-header-end">
		<?php
	}

	/**
	 * The Drift "D" monogram (brand pack, square-transparent.svg), 100×100.
	 *
	 * @param string $fill Fill colour.
	 * @param int    $size Width and height in px.
	 * @return string SVG markup.
	 */
	public static function mark_svg( $fill = 'currentColor', $size = 28 ) {
		$paths = array(
			'M17 12H54C77 12 91 29 91 50C91 72 76 88 54 88H45L55 76H54C70 76 79 64 79 50C79 35 69 24 54 24H29V44H17Z',
			'M17 55H29L17 67Z',
			'M41 55H54L25 88H12Z',
			'M55 76H70L59 88H43Z',
		);
		$svg   = sprintf( '<svg xmlns="http://www.w3.org/2000/svg" width="%1$d" height="%1$d" viewBox="0 0 100 100" aria-hidden="true" focusable="false">', (int) $size );
		foreach ( $paths as $d ) {
			$svg .= '<path fill="' . esc_attr( $fill ) . '" d="' . $d . '"/>';
		}
		return $svg . '</svg>';
	}

	/**
	 * Product mark: the monogram in the Gift Cards accent, and the product name.
	 */
	private static function product_mark() {
		?>
		<span class="drift-gift-cards-mark">
			<span class="drift-gift-cards-mark__icon"><?php echo self::mark_svg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed markup, fill escaped. ?></span>
			<span class="drift-gift-cards-mark__name"><?php echo esc_html( DRIFT_GIFT_CARDS_PRODUCT_NAME ); ?></span>
		</span>
		<?php
	}

	/**
	 * Tags allowed in the header lead.
	 *
	 * @return array
	 */
	private static function lead_tags() {
		return array(
			'a'      => array( 'href' => true ),
			'code'   => array(),
			'strong' => array(),
			'em'     => array(),
		);
	}
}
