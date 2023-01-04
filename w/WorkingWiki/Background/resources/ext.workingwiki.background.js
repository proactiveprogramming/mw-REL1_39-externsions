/**
 * ext.workingwiki.background.js : WorkingWiki background jobs interface
 */
(function( $, mw ) {

mw.libs.ext.ww.reloadJobsList = function( bypass_cache ) {
	$( '#ww-reload-button' ).attr( {
		'class':'ww-tiny-spinner' 
	} );
	projects = mw.config.get( 'wwProjectNames' );
	if ( projects.length === 0 ) {
		return;
	}
	bypass_cache = (bypass_cache ? 1 : 0); // need integer for MW api
	actionname = 'ww-list-background-jobs';
	mw.libs.ext.ww.api(
		{
			action : actionname,
			projects : projects.join('|'),
			'bypass-cache' : bypass_cache
		}, {
			successMessage : null,
			ok : function( data, apiCall, opts ) {
				if ( apiCall.action in data && 'list' in data[apiCall.action] ) {
					$( '#wwb-jobs-message' )
						.replaceWith( data[apiCall.action].list );
				} else {
					mw.libs.ext.ww.notify(
						apiCall.action +
						' action succeeded, but did not return a new listing.'
					);
				}
				mw.libs.ext.ww.jobsListReady( 'unbroken' );
			}, 
			err : function( code, result, messages ) {
				// TODO: should not request it if no read permission
				if ( code != 'http' && code != 'readapidenied' ) {
					mw.libs.ext.ww.notify(
						'Error reloading list of background jobs: ' +
						code + ': ' + result.error.info
					);
				}
				mw.libs.ext.ww.jobsListReady( 'broken' );
			}
		},
		false
	);
};

mw.libs.ext.ww.startReloadInterval = function() {
	if ( mw.libs.ext.ww.backgroundJobsReloadInterval ) {
		clearInterval( mw.libs.ext.ww.backgroundJobsReloadInterval );
	}
	mw.libs.ext.ww.backgroundJobsReloadInterval =
		setInterval( function() { mw.libs.ext.ww.reloadJobsList( false ); }, 60 * 1000 );
};

mw.libs.ext.ww.jobsListReady = function( button_status ) {
	// each time the jobs list is loaded, add the javascript actions to it
	var button_class = 'ww-reload-button-'+button_status;
	var wrb = $( '#ww-reload-button' );
	var wjm = $( '#wwb-jobs-message' );
	if ( wjm.children().length == 1 && wrb.length == 1 ) {
		wrb.parent().remove();
	} else if ( wrb.length === 0 && wjm.children().length > 0 ) {
		wjm.prepend(
			$( '<div>' ).attr( {
				'class': 'ww-reload-button-container'
			} ).prepend( $( '<div>' ).attr( {
				id : 'ww-reload-button',
				'class': 'ww-reload-button '+button_class,
				title : 'Reload' // TODO add hotkey
			} ).click( function() {
				mw.libs.ext.ww.reloadJobsList( true );
				mw.libs.ext.ww.startReloadInterval();
			} ) )
		);
	} else if ( wrb.length > 0 ) {
		wrb.attr( {
			'class': 'ww-reload-button '+button_class
		} );
	}
};

$( function() {
	// set recurrent timer to update the list of background jobs
	projects = mw.config.get( 'wwProjectNames' );
	if ( projects.length ) {
		// TODO for future: if projects can be added to the page,
		// need to start this interval
		mw.libs.ext.ww.startReloadInterval();
	}
	// the initial jobs list comes with the page, though subsequently
	// it'll be updated by ajax.
	mw.libs.ext.ww.jobsListReady( 'unbroken' );
} );

})( $, mw );
