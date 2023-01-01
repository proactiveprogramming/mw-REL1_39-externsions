(function ( $, mw ) {

window.clickTo = function ( event, api ) {
	// do what wwlink does - call the action via api - and then
	// remove the message where the link is once it's done
	var $to_remove = $( event.target ).parent( '.ww-clickto' );
	if ( 'hook' in mw && ! $.isEmpty( $to_remove ) ) {
		var rmv;
	       	rmv = function ( result, apiCall, opts ) {
			var $fs = $to_remove.parent( 'fieldset.ww-messages' );
			$to_remove.remove();
			if ( ! $fs.children().not( 'legend' ).text() ) {
				$fs.remove();
			}
			mw.hook( 'ww-api-ww-set-source-file-location-ok' ).remove( rmv );
			mw.hook( 'ww-api-ww-set-project-file-location-ok' ).remove( rmv );
			mw.hook( 'ww-api-ww-remove-file-ok' ).remove( rmv );
		};
		mw.hook( 'ww-api-ww-set-source-file-location-ok' ).add( rmv );
		mw.hook( 'ww-api-ww-set-project-file-location-ok' ).add( rmv );
		mw.hook( 'ww-api-ww-remove-file-ok' ).add( rmv );
	}
	wwlink( event, api );
}; 

})( $, mw )
