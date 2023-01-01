/**
 * ext.workingwiki.api.js : WorkingWiki version of mw.Api.get()
 */
(function( $, mw ) {

$.extend( mw.libs.ext.ww, {

	api : function ( apiCall, opts, comet ) {
		if ( ! ( 'action' in apiCall ) ) {
			mw.libs.ext.ww.defaultApiErrFn(
				'noaction',
				undefined,
				mw.message( 'ww-api-no-action' ).plain()
			);
			return;
		}
		// possibly forward to Comet framework instead of regular Ajax
		if ( comet === undefined && mw.config.get( 'wwUseComet' ) ) {
			comet = true;
		}
		if ( comet ) {
			var cometContext = (comet === true) ? false : comet;
			mw.loader.using( 'ext.workingwiki.comet', function () {
				mw.libs.ext.ww.doComet( apiCall, opts, cometContext );
			} );
		} else {
			// no Comet, use Ajax
			opts = $.extend( true, {
				ok : mw.libs.ext.ww.defaultApiOkFn,
				err : mw.libs.ext.ww.defaultApiErrFn,
				done : mw.libs.ext.ww.defaultApiDoneFn
			}, opts );
			var apiopts = {};
			if ( 'ajax' in opts ) {
				apiopts.ajax = opts.ajax;
				//console.log( 'api action "' + apiCall.action + '", timeout=' + opts.ajax.timeout );
			}
			if ( 'type' in opts ) {
				apiopts.type = opts.type;
			}
			( new mw.Api( apiopts ) ).get( apiCall )
				.done( function( data ) {
					opts.ok( data, apiCall, opts );
					opts.done( apiCall, opts );
				} )
				.fail( function( code, result ) {
					var messages = '';
					if ( 'action' in apiCall && 
						apiCall.action in result &&
						'messages' in result[apiCall.action] ) {
						messages = result[apiCall.action].messages;
					}
					opts.err( code, result, messages, apiCall );
					opts.done( apiCall, opts );
				} );
		}
	},

	// backward compatibility for mw.hook
        fireHook : function ( hookkey ) {
		var arglist = Array.prototype.slice.call( arguments, 1 )
		if ( 'hook' in mw ) {
			mw.hook( hookkey ).fire.apply( null, arglist );
		} else if ( hookkey in mw.libs.ext.ww.hooks ) {
			mw.libs.ext.ww.hooks[ hookkey ].apply( null, arglist );
		}	
	},

	// stub for the real okfn
	defaultApiOkFn : function ( result, apiCall, opts ) {
		mw.loader.using( 'ext.workingwiki', function () {
			mw.libs.ext.ww.defaultApiOkFn( result, apiCall, opts );
		} );
	},

	//stub
	defaultApiErrFn : function ( code, result, messages, apiCall ) {
		mw.loader.using( 'ext.workingwiki', function () {
			mw.libs.ext.ww.defaultApiErrFn( code, result, messages, apiCall );
		} );
	},

	// not a stub - too short
	defaultApiDoneFn : function ( apiCall, opts ) {
		var hookkey = 'ww-api-' + apiCall.action + '-done';
		mw.libs.ext.ww.fireHook( hookkey );
		if ( 'spinnerId' in opts ) {
			mw.libs.ext.ww.removeTinySpinner( opts.spinnerId );
		}
	},

} );

})( $, mw );
