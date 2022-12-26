// Handles the interface for actually marking an article as reviewed
//

( function () {
	mw.pageTriage.action = {
		submit: function () {
			return new mw.Api().postWithToken( 'csrf', {
				action: 'pagetriageaction',
				pageid: mw.config.get( 'wgArticleId' ),
				reviewed: '1'
			} )
				.then( function () {
					$( '.mw-pagetriage-markpatrolled' ).text( mw.msg( 'pagetriage-reviewed' ) );
				} )
				.catch( function ( _errorCode, data ) {
					$( '.mw-pagetriage-markpatrolled' ).text( mw.msg( 'pagetriage-mark-as-reviewed-error', data.error.info ) );
				} );
		}
	};

	$( function () {
		$( '.mw-pagetriage-markpatrolled-link' )
			.on( 'click', function () {
				mw.pageTriage.action.submit();
				return false;
			} );
	} );
}() );