$( function () {
	/* eslint-disable */
	var $loader = $( '<div class="mw-css-loader-preferenceslist"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>' );
	$( '.form-pref-list > form' ).on( 'submit', function () {
		$( this ).hide();
		$( '#mw-content-text' ).append( $loader );
	} );
	/* eslint-enable */
} );
