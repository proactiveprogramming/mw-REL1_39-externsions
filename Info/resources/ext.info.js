
mw.loader.using( [ 'mediawiki.api', 'mediawiki.notify' ] ).then( function () {

	$( "#ca-info a" ).on( 'click', function ( e ) {
		var postArgs = { action: 'info', titles: mw.config.get( 'wgPageName' ) };
		new mw.Api().post( postArgs ).then( function () {
			location.reload();
		}, function () {
			mw.notify( mw.msg( 'info-failed' ), { type: 'error' } );
		} );
		e.preventDefault();
	} );

} );
