( function ( $ ) {
	'use strict';

	$( function () {
		var $form   = $( '#drift-gift-cards-balance-form' );
		if ( ! $form.length ) {
			return;
		}

		var $input  = $( '#drift-gift-cards-balance-code' );
		var $result = $( '#drift-gift-cards-balance-result' );
		var i18n    = ( window.driftGiftCardsBalance && window.driftGiftCardsBalance.i18n ) || {};

		$form.on( 'submit', function ( e ) {
			e.preventDefault();
			$result.text( i18n.checking || 'Checking…' );

			$.ajax( {
				url: window.driftGiftCardsBalance.restUrl,
				method: 'POST',
				contentType: 'application/json',
				data: JSON.stringify( { code: $input.val() } ),
			} )
				.done( function ( data ) {
					if ( data.success ) {
						$result.text( 'Balance: £' + parseFloat( data.balance ).toFixed( 2 ) + ' (' + data.status + ')' );
					} else {
						$result.text( data.message || i18n.error || 'Something went wrong.' );
					}
				} )
				.fail( function ( xhr ) {
					var message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : ( i18n.error || 'Something went wrong. Please try again.' );
					$result.text( message );
				} );
		} );
	} );
} )( jQuery );
