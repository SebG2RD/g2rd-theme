/* global G2RDPortfolio, jQuery */
jQuery( function ( $ ) {
	$( document ).on( 'click', '.toggle-password', function () {
		var $btn  = $( this );
		var $mask = $btn.siblings( '.password-mask' );

		// Masquer si déjà révélé
		if ( $btn.data( 'revealed' ) ) {
			$mask.text( '••••••••' );
			$btn.removeClass( 'dashicons-hidden' ).addClass( 'dashicons-visibility' );
			$btn.data( 'revealed', false );
			return;
		}

		// Révéler via AJAX
		$.post( G2RDPortfolio.ajaxUrl, {
			action:  'g2rd_get_portfolio_password',
			nonce:   G2RDPortfolio.nonce,
			post_id: $btn.data( 'postId' ),
		} ).done( function ( response ) {
			if ( response.success ) {
				$mask.text( response.data.password || '(vide)' );
				$btn.removeClass( 'dashicons-visibility' ).addClass( 'dashicons-hidden' );
				$btn.data( 'revealed', true );
			}
		} );
	} );
} );
