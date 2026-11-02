( function ( $ ) {
	'use strict';

	$( function () {
		var $select = $( '#drift_gift_cards_amount' );
		if ( ! $select.length ) {
			return;
		}

		var $customRow   = $( '.drift-gift-cards-custom-amount-row' );
		var $customInput = $( '#drift_gift_cards_amount_custom' );

		$select.on( 'change', function () {
			if ( 'custom' === $select.val() ) {
				$customRow.show();
				$customInput.attr( 'name', 'drift_gift_cards_amount' );
				$select.removeAttr( 'name' );
			} else {
				$customRow.hide();
				$customInput.removeAttr( 'name' );
				$select.attr( 'name', 'drift_gift_cards_amount' );
			}
		} );
	} );

	$( function () {
		var $hardCopy    = $( '#drift_gift_cards_hard_copy' );
		if ( ! $hardCopy.length ) {
			return;
		}

		var $addressBox  = $( '.drift-gift-cards-hard-copy-address' );
		var $requiredFields = $addressBox.find( '#drift_gift_cards_ship_name, #drift_gift_cards_ship_address_1, #drift_gift_cards_ship_city, #drift_gift_cards_ship_postcode, #drift_gift_cards_ship_country' );

		$hardCopy.on( 'change', function () {
			if ( $hardCopy.is( ':checked' ) ) {
				$addressBox.show();
				$requiredFields.attr( 'required', 'required' );
			} else {
				$addressBox.hide();
				$requiredFields.removeAttr( 'required' );
			}
		} );
	} );
} )( jQuery );
