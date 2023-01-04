/**
 * Regular Tooltips
 *
 * @author Sanjay Thiyagarajan
 */

( function ( mw, $ ) {
	'use strict';

	/**
	 * Decodes html-encoded string
	 *
	 * @param {string} input
	 * @return {string}
	 */
	function htmlDecode( input ) {
		var e = document.createElement( 'textarea' );
		e.innerHTML = input;
		return e.childNodes.length === 0 ? '' : e.childNodes[ 0 ].nodeValue;
	}

	/**
	 * @param {Object} $context
	 * @param {boolean} $multiple
	 */
	var toolTipTrigger = function ( $context, $multiple = false ) {

		if ( $multiple ) {
			$context = $context.find( '.regular-tooltips' );
		}

		$context.each( function () {
			var text = $( this ).attr( 'data-regular-tooltips' );
			text = htmlDecode( text );

			var popup = new OO.ui.PopupButtonWidget( {
				icon: 'info',
				framed: false,
				popup: {
					padded: true,
					align: 'force-right',
					$content: $( '<p>' + text + '</p>' )
				}
			} );

			$( this ).append( popup.$element );

		} );

	};

	$( function () {
		mw.hook( 'pf.addTemplateInstance' ).add( function ( $elements ) {
			toolTipTrigger( $elements, true );
		} );
		$( '.regular-tooltips' ).each( function () {
			if( $( this ).closest( '.multipleTemplateList' ).length === 0 ) {
				toolTipTrigger( $( this ), false );
			}
		} );
	} );

}( mediaWiki, jQuery ) );
