jQuery( function ( $ ) {
	'use strict';

	var frame;

	$( '#drift-gift-cards-upload-image' ).on( 'click', function ( e ) {
		e.preventDefault();

		if ( frame ) {
			frame.open();
			return;
		}

		var strings = window.driftGiftCardsAdmin || {};

		frame = wp.media( {
			title: strings.mediaTitle || 'Select gift card image',
			button: { text: strings.mediaButton || 'Use this image' },
			multiple: false,
		} );

		frame.on( 'select', function () {
			var attachment = frame.state().get( 'selection' ).first().toJSON();
			$( '#drift_gift_cards_gift_card_image_id' ).val( attachment.id );
			// Built with .attr() rather than an HTML string so the URL can't inject markup.
			$( '#drift-gift-cards-image-preview' ).empty().append(
				$( '<img>' ).attr( { src: attachment.url, alt: strings.previewAlt || '' } )
			);
			$( '#drift-gift-cards-remove-image' ).prop( 'hidden', false );
		} );

		frame.open();
	} );

	$( '#drift-gift-cards-remove-image' ).on( 'click', function ( e ) {
		e.preventDefault();
		$( '#drift_gift_cards_gift_card_image_id' ).val( '' );
		$( '#drift-gift-cards-image-preview' ).empty();
		$( this ).prop( 'hidden', true );
	} );

	$( document ).on( 'click', '.drift-gift-cards-confirm-disable', function ( e ) {
		var message = ( window.driftGiftCardsAdmin && window.driftGiftCardsAdmin.confirmDisable ) || 'Disable this gift card?';
		if ( ! window.confirm( message ) ) { // eslint-disable-line no-alert
			e.preventDefault();
		}
	} );

	$( document ).on( 'click', '.drift-gift-cards-toggle-edit', function ( e ) {
		e.preventDefault();
		var $row = $( '.drift-gift-cards-edit-row' ).filter( '[data-code="' + $( this ).data( 'code' ) + '"]' );
		$row.prop( 'hidden', ! $row.prop( 'hidden' ) );
	} );

	$( document ).on( 'click', '.drift-gift-cards-toggle-redeem', function ( e ) {
		e.preventDefault();
		var $row = $( '.drift-gift-cards-redeem-row' ).filter( '[data-code="' + $( this ).data( 'code' ) + '"]' );
		$row.prop( 'hidden', ! $row.prop( 'hidden' ) );
	} );
} );
