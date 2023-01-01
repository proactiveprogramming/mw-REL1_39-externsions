(function ( $, mw ) {

// if mw.hook available, use it to reload the directory listing
// when directory is cleared or synced.
// TODO: also on 'remove' action.
if ( 'hook' in mw ) {
	var recv_repl = function ( result, apiCall, opts ) {
		$( '.ww-gpf-ls' ).replaceWith( $( result.html ) );
	};
	var call_repl = function ( result, apiCall, opts ) {
		var $fn = $( '.ww-gpf-ls th.filename' );
		if ( $.isEmpty( $fn ) ) {
			$fn = $( '.ww-gpf-ls' );
		}
		mw.libs.ext.ww.injectTinySpinner( $fn, 'reload-directory' );
		mw.libs.ext.ww.api( {
			'action' : 'ww-list-directory',
			'directory' : mw.config.get( 'wwCurrentDirectory' ),
			'project' : mw.config.get( 'wwDefaultProjectName' ),
			'html' : 1
		}, {
			ok : recv_repl,
			spinnerId : 'reload-directory'
		} );
	};
	mw.hook( 'ww-api-ww-clear-directory-ok' ).add( call_repl );
	mw.hook( 'ww-api-ww-sync-all-ok' ).add( call_repl );
	mw.hook( 'ww-api-ww-sync-file-ok' ).add( call_repl );
	mw.hook( 'ww-api-ww-remove-file-ok' ).add( call_repl );
} 

})( $, mw );
