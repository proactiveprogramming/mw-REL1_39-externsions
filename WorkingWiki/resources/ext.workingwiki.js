/**
 * ext.workingwiki.js : global WorkingWiki code
 */
(function( $, mw ) {

/* 'private' data */
var lastClickTimestamp;

$.extend( mw.libs.ext.ww, {
	// use notify() if available,
	// dialog() if not (i.e. if in MW 1.19)
	notify : function( message, opts, title ) {
		// message could be a jQuery object, or just a string.
		try {
			mw.loader.using( [ 'mediawiki.notification', 'mediawiki.notify' ], function() {
				mw.notify( message, opts );
			} );
		} catch ( e ) {
			mw.loader.using( 'jquery.ui.dialog', function() {
				// TODO reuse the dialog
				if ( ! title )  {
					$div = $( '<div/>' );
				} else {
					$div = $( '<div title="'+title+'"/>' );
				}
				var d_opts = {
					buttons : [ {
						text : mw.message( 'ww-notify-ok' ).plain(),
						click : function () {
							$(this).dialog( 'close' );
						}
					} ]
				};
				$div.append( message ).dialog( d_opts );
				// TODO make it stretch to width of message
			} );
		}
	},

	defaultApiOkFn : function ( result, apiCall, opts ) {
		var successMessage;
		if ( 'successMessage' in opts ) {
			successMessage = opts.successMessage;
		} else {
			successMessage = mw.libs.ext.ww.makeApiMessage( apiCall, '-success' );
		}
		// if there's no entry in wwApiMessages, use the default message.
		// if there's a null entry, that returns null and we do nothing
		if ( ! successMessage ) {
			// except if there's messages from the server
			if ( apiCall.action in result && result[ apiCall.action ].messages ) {
				mw.libs.ext.ww.notify( $( '<div></div>' ).append( result[ apiCall.action ].messages ) );
			}
			return;
		}
		if ( apiCall.action in result &&
			result[ apiCall.action ].messages ) {
			successMessage = $( '<div></div>' )
				.text( successMessage )
				.append( '<br/>' + result[ apiCall.action ].messages );
			mw.libs.ext.ww.notify( successMessage );
		} else {
			// if we succeeded but there's no WW messages,
			// notify only in case we have a new enough wiki
			// to have the nice notification bubbles
			try {
				mw.loader.using(
					[ 'mediawiki.notification', 'mediawiki.notify' ], function () {
					mw.notify( successMessage, {} );
				} );
			} catch ( e ) { }
		}
		if ( 'hook' in mw ) {
			mw.hook( 'ww-api-' + apiCall.action + '-ok' ).fire( result, apiCall, opts );
		}
	},

	extractErrorText : function ( code, result ) {
		var errtxt = code;
		if ( 'error' in result && result.error.info ) {
			errtxt = 'Error: ' + result.error.info;
		} else if ( 'error' in result && result.error.code ) {
			errtxt = 'Error: ' + result.error.code;
		} else if ( result.exception ) {
			errtxt = 'Error: ' + result.exception;
		} else if ( code == 'http' ) {
			errtxt = 'Couldn\'t connect to server.';
		}
		return errtxt;
	},

	assembleMessages : function ( result, messages, prefix ) {
		var $errdiv = $( '<div/>' );
		if ( prefix ) {
			$errdiv.prepend( prefix );
		}
		if ( messages ) {
			$errdiv.append( '<br/>' + messages );
		}
		if ( result.error && result.error.messages ) {
			$errdiv.append( '<br/>' + result.error.messages );
		}
		return $errdiv;
	},


	defaultApiErrFn : function ( code, result, messages, apiCall ) {
		var $errdiv = mw.libs.ext.ww.assembleMessages( result, messages );
		var errtxt = mw.libs.ext.ww.extractErrorText( code, result );
		$errdiv.prepend( $( '<span/>' ).text( errtxt ) );
		mw.libs.ext.ww.notify( $errdiv, {}, 'Error' ); 
	},

	// do js operations on WW project file (and source file) elements.
	// this gets called when the page is loaded, and also when a project
	// file is loaded dynamically.
	fixUpProjectFiles : function ( $elts ) {

		$elts = $elts || $( '#content' );
		// when an HTML project file is loaded into a WW iframe, set up
		// a handler to resize the iframe when the project file's dimensions
		// change
		$elts.find( '.ww-project-file-iframe' ).each( function () {
			var $iframe = $( this );
			function fix_size () {
				var $body = $iframe.contents().find( 'body, svg' ).first();
				$body.css( { 'overflow-y':'hidden' } );
				$iframe.css( { height: $body.outerHeight( true ) } );
				setTimeout( fix_size, 1000 );
			};
			fix_size();
			// TODO: it's hard to catch resizing of the inner document!
			// how to do without the above polling logic?
			//$body[0].onresize = function () {
			//	$iframe.css( { height: $body[0].parentNode.offsetHeight + 2 } );
			//}
			//$body[0].onresize();
		} ).load();
	}

} );

$( function () {

	mw.libs.ext.ww.fixUpProjectFiles();

	// on edit pages, suppress the about-to-leave-page warning
	// for WW display=download links, by making the links load 
	// into a hidden iframe
	if ( wgAction == 'edit' || wgAction == 'submit' ) {
		var download_name = null;
		var iframe = $('<iframe name="ww-download-iframe" id="ww-download-iframe"/>');
		$( 'a[href*="?display=download"],a[href*="&display=download"],a[href*="&amp;display=download"]' )
			.click( function(e) {
				download_name = this.href
					.replace( /^.*filename=(.+)(&.*|)$/, "$1" );
				e.preventDefault();
				$('#ww-download-iframe').attr( 'src', this.href
					.replace( /\bdisplay=download\b/i, 'display=raw' ) );
			} );
		//.click( function(e) {
		//	e.preventDefault();
		//	iframe.attr( 'src', $(this).attr( 'href' ) );
			//window.onbeforeunload = null;
		//} );
		iframe.hide()
			.load( function() {
				// seems load event is triggered when the iframe
				// loads an error response, but not when it succeeds
				if ( ! download_name ) {
					return;
				}
				// TODO: internationalize this text
				var messages = $('#ww-download-iframe').contents().find('.ww-messages');
				if ( messages.length === 0 ) {
					messages = $(
						'<fieldset class="ww-messages">' +
						'<legend>WorkingWiki messages</legend>' +
						'Could not retrieve ' + download_name +
						'</fieldset>'
					);
				}
				mw.libs.ext.ww.notify( messages, {
					tag : 'download-' + download_name
				} );
			} );
		$('body').append(iframe);

		// also, on edit pages, do the JS half of this hack to provide
		// the custom edit buttons' src images as data urls
		// background-image values are set in ext.workingwiki.css
		// TODO: internationalize
		mw.loader.using("mediawiki.action.edit", function() {
			mw.toolbar.addButton('about:blank','source-file tag',
				'<source-file filename="">\n', '</source-file>',
				'Insert source file contents here, and fill in the filename.\n',
				'ww-edit-button-source-file');
			mw.toolbar.addButton('about:blank','project-file tag',
				'<project-file filename="', '"/>',
				'Insert project filename',
				'ww-edit-button-project-file');
			mw.toolbar.addButton('about:blank','Tab character',
				'\t', '', '',
				'ww-edit-button-tab');
			window.setTimeout( function() {
				$('*[id^="ww-edit-button-"]').each( function(i, e) {
					//alert( 'src before: ' + e.src );
					//alert( 'background before: ' + $(e).css('background-image' ) );
					e.src = $(e).css('background-image').replace(/^url\("?(.*?)"?\)$/,'$1');
					$(e).css('background-image', 'none');
					//alert( 'src after: ' + e.src );
				} );
			}, 0);
		} );
	}

	// make WW source code listings collapsible.
	$( "#content" ).on( 'click', '.ww-collapsible legend', function () {
		    $(this).parent().toggleClass( 'ww-collapsed' );
	} );

	// make pulldown altlinks menus pin when the triangle is clicked
	$( '#content' ).on( 'click', '.ww-altlinks', function ( e ) {
		if ( ! $( e.target ).parents().addBack().is( '.ww-altlinks-inner' ) ) {
			$(this).toggleClass( 'ww-pulldown-pinned' );
		}
	} );

	// to do after the document.ready stuff has executed
	setTimeout ( function () {
		// preload libraries for notifications, in case we need to
		// notify later that the network's down
		mw.loader.using( 'jquery.ui.dialog', function () {} );
		try {
			mw.loader.using(
				[ 'mediawiki.notification', 'mediawiki.notify' ],
			       function () {}
		       );
		} catch( e ) {
		}
	}, 2000 );

} );

})( $, mw );
