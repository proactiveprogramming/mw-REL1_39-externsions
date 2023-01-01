( function ( $, mw ) {

if ( ! mw.libs ) {
        mw.libs = {};
}
if ( ! mw.libs.ext ) {
        mw.libs.ext = {};
}

var spinnerCounter = 0;
var lastClickTimestamp = null;

// a small portion of ww code to be available during page load
mw.libs.ext.ww = {
	// simplistic hook feature, only for use in older wikis that don't
	// have mw.hook.  To be removed when we stop supporting those.
	hooks : {},

	// infer api parameters from contents of a form or link
	constructParams : function( element ) {
		var params = {}, api = {};
		if ( element.href ) {
			// parse the params from a link's href
			params = element.href.split(/\?|&amp;|&/);
		} else {
			// parse submit button's form data
			params = [];
			var $fm = $( element ).closest( 'form' );
			if ( $.isEmpty( $fm ) ) {
				$fm = $( element );
			}
			$fm.find( ':input' ).each(
				function () {
					if ( $(this).is( '[type=checkbox]' ) ) {
						if ( this.checked ) {
							params.push( this.name + '=on' );
						} // else nothing
					} else {
						params.push( this.name + '=' + this.value );
					}
				}
			);
		}
		for ( var i in params ) {
			if ( params[i].substr(0,9) == 'ww-action' ) {
				var kv = params[i].split(/=/);
				if ( kv[0] == 'ww-action' ) {
					api[ 'action' ] = 'ww-' + kv[1];
				} else {
					// urldecode
					// http://stackoverflow.com/questions/4292914
					var val = decodeURIComponent((kv[1]+'').replace(/\+/g, '%20'));
					api[ kv[0].substr(10) ] = val;
				}
			}
		}
		return api;
	},

	// when a ww link is clicked during page load, we don't want to use
	// the no-javascript fallback, because that would cause another page
	// load.  But during page load we don't yet have the JS code we need
	// to handle the click.  So we slap a spinner on the link and do
	// what it takes to load the code and do the processing ASAP.
        wwlink : function ( event, api, opts ) {
		event.preventDefault();
                // "debounce" by rejecting extra copies of the same click event
                if ( event.timeStamp == lastClickTimestamp ||
			event.timeStamp - lastClickTimestamp < 1000 ) {
                        if ( window.console && window.console.log ) {
                                console.log( 'ajax click, ' + event.timeStamp + ': rejected' );
                        }
                        return;
                }
                if ( window.console && window.console.log ) {
                        console.log( 'ajax click, ' + event.timeStamp + ' != ' + lastClickTimestamp + ': accepted' );
                }
                lastClickTimestamp = event.timeStamp;
		// put a spinner on it
		var spinnerId = 'defer-' + spinnerCounter;
		mw.libs.ext.ww.injectTinySpinner( $( event.target ), spinnerId );
		spinnerCounter += 1;
		if ( ! opts ) {
			opts = {};
		}
		$.extend( opts, { spinnerId : spinnerId } );
		// change the URL that would be called into API params
		if ( ! api ) {
			api = mw.libs.ext.ww.constructParams( event.target );
		}
		// confirm if needed
		if ( mw.message( api['action'] + '-confirm-message' ).exists() ) {
			mw.loader.using( 'ext.workingwiki.confirm', function () {
				mw.libs.ext.ww.confirmApi( api, opts );
			} );
			mw.loader.using( 'ext.workingwiki.api', function () {
				// preload while user is confirming
			} );
		} else {
			mw.loader.using( 'ext.workingwiki.api', function () {
				mw.libs.ext.ww.api( api, opts );
			} );
		}
        },

        createTinySpinner : function( id ) {
                return $( '<div>' ).attr( {
                        'class' : 'ww-tiny-spinner ww-tiny-spinner-'+id,
                        title : '...'
                } );
        },

        injectTinySpinner : function( elt, id ) {
                //this.removeTinySpinner( id );
                return elt.after( this.createTinySpinner( id ) );
        },

        removeTinySpinner : function( id ) {
                return $( '.ww-tiny-spinner-' + id ).remove();
        },

        shortProjectName : function( longProjectName ) {
                return longProjectName.replace(
                        new RegExp( '^' + mw.config.get( 'wwProjectUriBase' ) ),
                        ''
                );
        },

	projectUri : function( projectName ) {
		var uriBase = mw.config.get( 'wwProjectUriBase' );
		if ( projectName.substr( 0, uriBase.length ) !== uriBase ) {
			return uriBase + projectName;
		}
		return projectName;
	}
};

window.wwlink = mw.libs.ext.ww.wwlink;

// unset javascript-sensing cookie
// see https://sourceforge.net/p/workingwiki/bugs/362
document.cookie = 'WorkingWiki.no.js=; expires=Thu, 01-Jan-70 00:00:01 GMT; path=/';

} )( $, mw );
