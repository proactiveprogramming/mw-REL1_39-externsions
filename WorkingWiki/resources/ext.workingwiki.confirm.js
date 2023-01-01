/**
* ext.workingwiki.confirm.js : minimal code for popping up a confirmation
*
* designed to be quick-loaded if needed while page is still loading
*/
(function ( $, mw ) {

/* create a global 'ww' object */
$.extend( mw.libs.ext.ww, {

	confirm : function ( message, yestext, notext, yescallback, nocallback, closecallback ) {
		if ( ! yescallback ) {
			yescallback = function () {};
		}
		if ( ! nocallback ) {
			nocallback = function () {};
		}
		if ( ! closecallback ) {
			closecallback = function () {};
		}
		var opts = { 
			buttons: [ {
				// NOTE the first button gets the focus
				text : yestext,
				click : function() { yescallback(); $(this).dialog( 'close' ); }
			}, {
				text: notext,
				click : function() { nocallback(); $(this).dialog( 'close' ); }
			} ],
			beforeClose: closecallback
		};
		try {
			message.dialog( opts );
		} catch ( e ) {
			$( '<div>'+message+'</div>' ).dialog( opts );
		}
	},

	makeApiMessage : function ( apiCall, tag ) {
		var messageData = mw.config.get( 'wwApiMessages' );
		var argNames = null;
		var action = apiCall.action;
		var msg = mw.message( action + tag );
		if ( msg.exists() ) {
			if ( action in messageData && 'args' + tag in messageData[ action ] ) {
				argNames = messageData[ action ][ 'args' + tag ];
			} else if ( action in messageData && 'args' in messageData[ action ] ) {
				argNames = messageData[ action ][ 'args' ];
			}
		} else {
			msg = mw.message( 'ww-default' + tag );
		}
		if ( ! argNames ) {
			if ( 'args' + tag in messageData[ 'default' ] ) {
				argNames = messageData[ 'default' ][ 'args' + tag ];
			} else {
				argNames = messageData[ 'default' ][ 'args' ];
			}
		}
		var messageArgs = [ msg.key ];
		// entries in 'args' are names of apiCall values
		for ( var i in argNames ) {
			messageArgs[ (+i) + 1 ] = apiCall[ argNames[i] ];
		}
		var msgtxt = mw.message.apply( null, messageArgs ).parse();
		if ( apiCall['preview-key'] ) {
			msgtxt += ' ' + mw.message(
				'ww-in-preview-session',
				apiCall['preview-key']
			).parse();
		}
		return msgtxt;
	},

	confirmApi : function ( apiCall, opts ) {
		var yesClicked = false;
		mw.libs.ext.ww.confirm(
			mw.libs.ext.ww.makeApiMessage( apiCall, '-confirm-message' ),
			mw.message( apiCall.action + '-confirm-button' ).plain(),
			mw.message( 'ww-cancel' ).plain(),
			function () {
				yesClicked = true;
				mw.libs.ext.ww.api( apiCall, opts );
			},
			null,
			function () {
				if ( ! yesClicked && 'spinnerId' in opts ) {
					mw.libs.ext.ww.removeTinySpinner( opts.spinnerId );
				}
			} 
		);
	}
} );

})( $, mw );
