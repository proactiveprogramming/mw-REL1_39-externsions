/*
 * ext.workingwiki.comet.js
 *
 * client-side code for Comet (Server-Side Events) interface to
 * WorkingWiki operations.
 */
( function( $, mw ) {

if ( mw.config.get( 'wwUseComet' ) ) {

	// prototype for controller objects for comet actions
	// reference: http://davidwalsh.name/javascript-objects-deconstruction
	CometAction = {

		// configure Comet data.  This can be called again to reconfigure
		// the object for a followup operation.
		init : function ( apiCall, opts ) {
			// basically, wipe and reset everything except the dialog
			this.closeConnection();
			this.apiCall = apiCall;
			if ( ! opts ) {
				opts = { done : false, ok : false, err : false };
			}
			this.key = this.generateKey();
			this.lastPosition = 0;
			this.opts = opts;
			this.done = opts.done || mw.libs.ext.ww.defaultCometDoneFn;
			this.okfn = opts.ok || mw.libs.ext.ww.defaultCometOKFn;
			this.errfn = opts.err || mw.libs.ext.ww.defaultCometErrFn;
			// state is one of: idle, connecting, connected
			this.state = 'idle';
			delete this.subscribeUrl;
			delete this.esource;
			var title =
				apiCall ? 
				mw.message( 'ww-comet-dialog-action-title', mw.libs.ext.ww.makeApiMessage( apiCall, '-status' ) ).parse() :
				'';
			delete this.connecting, this.connected, this.connectionAttempts;
			this.createDialog();
			this.$dialog_div.dialog( {
				title : title
			} );
			// TODO: prevent leaving the page while a comet operation is running
			return this;
		},

		generateKey : function () {
			// https://gist.github.com/Dreyer/2368164
			var m = 9, s = '', r = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
			for (var i=0; i < m; i++) { s += r.charAt(Math.floor(Math.random()*r.length)); }
			return s;
		},

		openhandler : function ( event ) {
			//console.log( 'comet open' );
			if ( this.state == 'connecting' ) {
				this.appendMessage( mw.message( 'ww-comet-connected' ).plain(), true );
			}
			this.state = 'connected';
		},

		messagehandler : function ( event ) {
			var text = event.data;
			var endpos = event.lastEventId;
			//console.log( 'comet message: ' + text.replace( /\n/, '\\n' ) );
			this.connectionAttempts = 0;
			// todo: somehow check that an event hasn't been skipped
			//if ( endpos - text.length != this.lastPosition ) {
			if ( 0 ) {
				//if ( this.lastPosition > 0 ) {
				if ( endpos - text.length > this.lastPosition ) {
					//console.log( 'lastPosition is ' + this.lastPosition
					//	+ ', endpos ' + endpos 
					//	+ ' and length ' + text.length );
					this.appendMessage( mw.message( 'ww-comet-lost-data' ).plain() );
					this.closeConnection();
					this.connectToWiki( this.lastPosition );
					return;
				} else {
					if ( endpos >= this.lastPosition ) {
						text = text.substr( this.lastPosition - (endpos - text.length) );
						//from = this.lastPosition;
					} else {
						// just wait until something useful comes
						return;
					}
				}
				//this.appendMessage( '...' );
			}
			// html-escape the text and put html line breaks
			text = $( '<span/>' ).text( text )
				.html().replace( /\n/g, '<br/>\n' );
			//if ( this.lastPosition === 0 ) {
			//	this.appendText( '<br/>' );
			//}
			this.appendText( text );
			var lengthLimit = 1000000; // enough is enough
			var st = this.$spool.text();
			if ( st.length > lengthLimit ) {
				this.$spool.text( '...<br/>' + st.substr( st.length - lengthLimit ) );
				this.fixScroll();
			}
			this.lastPosition = endpos;
			this.keepAlive();
		},

		keepalivehandler : function ( event ) {
			//console.log( 'comet keep-alive' );
			this.connectionAttempts = 0;
			this.appendMessage( '(keep alive)' );
			this.keepAlive();
		},

		updatesurlhandler : function ( event ) {
			//console.log( 'comet updates-url' );
			this.subscribeUrl = event.data;
			//this.closeConnection();
			this.lastPosition = 0;
			this.connectToWiki();
		},

		resulthandler : function ( event ) {
			var comet = this;
			var result = event.data;
			//console.log( 'comet result: ' + result );
			this.closeConnection();
			//this.appendMessage( mw.message( 'ww-comet-done' ).plain() );
			this.$dialog_div.dialog( {
				title : mw.message( 'ww-comet-dialog-title' ).plain()
			} );
			var ok = true;
			if ( result === undefined || result === null || result === '' ) {
				ok = false;
			} else {
				result = $.parseJSON( result );
				if ( result === undefined || result === null || result === '' ) {
					ok = false;
				} else if ( 'error' in result || 'exception' in result ) {
					ok = false;
				}
			}
			// TODO: handle messages in result data
			if ( ok ) {
				var $messages = mw.libs.ext.ww.assembleMessages( result );
				$messages.find( '.ww-messages > p' ).each( function () {
					comet.appendText( $( this ).text() + '\n' );
				} );
				var successmessage = this.opts.successmessage || mw.libs.ext.ww.makeApiMessage( this.apiCall, '-success' );
				this.appendMessage( successmessage );
				if ( $messages.text() ) {
					mw.libs.ext.ww.notify( $messages );
				}
				this.okfn( result, this.apiCall, this.opts, this );
			} else {
				$messages = mw.libs.ext.ww.assembleMessages( result );
				$messages.find( '.ww-messages > p' ).each( function () {
					comet.appendText( $( this ).text() + '\n' );
				} );
				comet.appendText( mw.libs.ext.ww.extractErrorText( 'error', result ) + '\n' );
				if ( $messages.text() ) {
					mw.libs.ext.ww.notify( $messages );
				}
				this.errfn( 'cometerror', result, undefined, this.apiCall, this );
			}
			this.keepOpen( true );
		},

		donehandler : function ( event ) {
			//console.log( 'comet done' );
			this.appendMessage( mw.message( 'ww-comet-done' ).plain() );
			this.$dialog_div.dialog( {
				title : mw.message( 'ww-comet-dialog-title' ).plain()
			} );
			this.keepOpen( true );
			// how to indicate running vs. done?
			this.closeConnection();
		},

		errorhandler : function ( event ) {
			console.log( 'comet error, ' + this.connectionAttempts + ' connection attempts' );
			if ( this.state == 'connected' && ! event.data ) {
				// if we've been connected, this may be a routine
				// timeout.  Just wait for the timer to reconnect.
				this.appendMessage( mw.message( 'ww-comet-lost-connection' ).plain() );
				this.state = 'connecting';
				++this.connectionAttempts;
			} else {
				var errortext;
				if ( 'data' in event ) {
					errortext = event.data;
					this.connectionAttempts = 0;
				} else {
					errortext = mw.message( 'ww-comet-could-not-connect' ).plain();
					++this.connectionAttempts;
				}
				/*
				this.closeConnection();
				*/
				//mw.notify( mw.message( 'ww-comet-error', errortext ).plain() );
				this.appendMessage( mw.message( 'ww-comet-error', errortext ).plain() );
			}
			if ( this.connectionAttempts > 5 ) {
				this.appendMessage( mw.message( 'ww-comet-connection-failed' ).plain() );
				this.closeConnection();
			} else if ( this.esource.readyState == 2 ) {
				this.connectToWiki();
			}
		},

		// make the call to the wiki API
		connectToWiki : function () {
			//console.log( 'comet connectToWiki' );
			if ( this.esource ) {
				this.esource.close();
			}
			if ( this.subscribeUrl ) {
				this.esource = new EventSource(
					this.subscribeUrl + '&operation[from]=' + this.lastPosition
					/*, { 'withCredentials' : true }*/
				);
				this.appendMessage( mw.message( 'ww-comet-subscribing' ).plain() );
			} else {
				var params = { 'logkey': this.key };
				if ( mw.config.get( 'wwBackwardCompatibleComet' ) ) {
					params['sse'] = true;
				} else {
					params['format'] = 'sse';
				}
				for ( var k in this.apiCall ) {
					if ( this.apiCall[k] !== undefined && this.apiCall[k] !== null ) {
						params[k] = this.apiCall[k];
					}
				}
				var url = mw.util.wikiScript( 'api' ) + '?' + $.param( params );
				this.esource = new EventSource( url/*, { 'withCredentials' : true }*/ );
				this.appendMessage( mw.message( 'ww-comet-opening', this.apiCall.action ).plain() );
			}
			this.state = 'connecting';
			this.connectionAttempts = 0;
			var comet = this;
			this.esource.onopen = function ( event ) {
				comet.openhandler( event );
			};
			/*
			// Why is this called all the time?
			this.esource.onerror = function ( event ) {
				comet.appendMessage( 'onerror() called' );
			};
			*/

			// When text arrives, append it to the existing text
			// It's got a byte id at the front, with a semicolon after
			this.esource.onmessage = function ( event ) {
				comet.messagehandler( event );
			};

			// A keep-alive event just tells us we can postpone reconnecting
			this.esource.addEventListener( 'keepalive', function ( event ) {
				comet.keepalivehandler( event );
			} );
			// If the source says it's done, stop it from trying to reload
			this.esource.addEventListener( 'done', function ( event ) {
				comet.donehandler( event );
			} );
			if ( ! this.subscribeUrl ) {
				this.esource.addEventListener( 'updates-url', function ( event ) {
					comet.updatesurlhandler( event );
				} );
			}
			// Receive the final result of the operation
			this.esource.addEventListener( 'result', function ( event ) {
				comet.resulthandler( event );
			} );
			// If the source reports an error, display it in place of the text
			this.esource.addEventListener( 'error', function ( event ) {
				comet.errorhandler( event );
			} );

			this.keepAlive();
		},

		fixScroll : function () {
			if ( this.autoscroll ) {
				if ( this.$spool.parent().hasClass( 'ww-comet-expanded' ) ) {
					var height = this.$spool[0].scrollHeight;

					this.$spool.css( { 'margin-top' : 0 } );
					//this.$spool.stop().animate( { 'scrollTop': height }, 1000 );
					this.$spool.scrollTop( height );
				} else {
					this.$spool.css( {
						'margin-top' : this.$spool.parent().height() - this.$spool.height()
					} );
				}
			}
			// don't let the changes in the spool mess with its width
			this.$spool.width( this.$spool.parent().width() - 17 );
		},

		appendText : function ( message, continueLine, italic ) {
			var dpos, dheight;
			if ( this.$dialog && ! this.$dialog.hasClass( 'ui-dialog-resizing' ) ) {
				dpos = this.$dialog.offset();
				dpos = {
					top : dpos.top - $(window).scrollTop(),
					left : dpos.left - $(window).scrollLeft()
				};
				dheight = this.$dialog.height();
			}
			if ( ! this.$spool ) {
				this.$spool = $( '<span/>' );
			}
			var text = this.$spool.text();
			if ( text.length > 0 ) {
				var $last = this.$spool.contents().last();
				if ( continueLine ) {
					this.$spool.append( ' ' );
				} else if ( ( italic || this.lastAppendedItalic ) &&
					    ! ( $last.is( 'br' ) || $last.text().substr( -1 ) == '\n' ) ) {
					this.$spool.append( '<br/>' );
				}
			}
			if ( italic ) {
				this.$spool.append( $( '<em/>' ).append( message ) );
			} else {
				this.$spool.append( message );
			}
			this.fixScroll();
			this.lastAppendedItalic = italic;
			if ( this.$dialog && ! this.$dialog.hasClass( 'ui-dialog-resizing' ) ) {
				// as the dialog's height changes, keep its bottom still
				this.$dialog.css( { 
					top : dpos.top + dheight - this.$dialog.height()
				} );
				//dpos.top += dheight - this.$dialog.height();
				//this.$dialog.offset( dpos );
			}
			this.keepOpen( false );
		},

		appendMessage : function ( message, continueLine ) {
			return this.appendText( message, continueLine, true );
		},

		stack : [],

		createDialog : function () {
			if ( this.$dialog ) {
				this.$dialog_div.dialog( 'open' );
				return;
			}
			// $spool is the div where the file contents go
			if ( ! this.$spool ) {
				this.$spool = $( '<span/>' );
			}
			this.$spool.addClass( 'ww-comet-spool' )
				.attr( 'id', 'ww-comet-spool-' + this.key );
			// we'll keep scrolling to the bottom of the text
			this.autoscroll = true;
			// but if someone scrolls it by hand, we'll yield control
			var comet = this;
			this.$spool.bind('scroll mousedown wheel DOMMouseScroll mousewheel keyup', function ( event ) {
				if ( event.which > 0 || event.type == 'mousedown' || event.type == 'mousewheel' ) {
					comet.$spool.stop();
					comet.autoscroll = false;
					// if they scroll to the bottom and stop there,
					// reeactivate autoscrolling
					clearTimeout( comet.autoscrollTimer );
					comet.autoscrollTimer = setTimeout( function () {
						if ( comet.$spool.scrollTop() + comet.$spool.height() >=
							comet.$spool[0].scrollHeight ) {
							comet.autoscroll = true;
						}
					}, 1000 );
				}
			} );
			// roll those together
			this.$container = $( '<div>' )
			       .attr( 'id', 'ww-comet-notification-' + this.key )
			       .addClass( 'ww-comet-notification-div' );
			this.$container
				.append( $( '<span>' )
					.addClass( 'ww-comet-expand-button' )
					.click( function () {
						var dpos = comet.$dialog.offset();
						dtop = dpos.top - $(window).scrollTop();
						var height = comet.$dialog.height();
						var $ndiv = $(this).parent( '.ww-comet-notification-div' );
						$ndiv.toggleClass( 'ww-comet-expanded' );
						if ( ! $ndiv.hasClass( 'ww-comet-expanded' ) ) {
							comet.autoscroll = true;
						}
						comet.fixScroll();
						comet.$dialog.css( { 
							top : dtop + height - comet.$dialog.height()
						} );
						comet.keepOpen( 'touched' );
					} )
				)
				.append( this.$spool )
				/*.append( $( '<span>' )
					.addClass( 'ww-comet-close-button' )
					.click( function () {
						comet.closeConnection();
						comet.$container.parent().remove();
					} )
				)*/;
			/*
			// and display it in a notification bubble
			mw.notify( this.$container, { 
				tag: 'ww-comet-notification-' + this.key,
				autoHide: false       
			} );
			*/
			/*
			// and pop it up at the bottom left of the screen
			var $lane = $( '.ww-comet-notification-lane' );
			if ( $.isEmpty( $lane ) ) {
				$lane = $( '<div/>' )
					.addClass( 'ww-comet-notification-lane' );
				$( 'body' ).append( $lane );
			}
			$lane.append( $( '<div/>' )
				.addClass( 'ww-comet-notification-outer-div' )
				.append( this.$container )
			);
			*/
			// and put it in a dialog at the bottom left
			this.$dialog_div = $( '<div/>' )
				.addClass( 'ww-comet-notification-outer-div' )
				.append( this.$container );
			this.$dialog_div.dialog( {
				beforeClose : function () {
					comet.closeConnection();
					CometAction.stack = $.grep(
						CometAction.stack,
						function ( $e ) {
							return ! $e.is( comet.$dialog );
						}
					);
				},
				closeOnEscape : false,
				closeText : 'kill', // todo: kill on first click, close on second click?
				create : function ( event ) {
					$( event.target ).parent().css( { position: 'fixed' } );
				},
				dialogClass : 'ww-comet-dialog ww-comet-dialog-' + this.key,
				dragStart: function () {
					CometAction.stack = $.grep(
						CometAction.stack,
						function ( $e ) {
							return ! $e.is( comet.$dialog );
						}
					);
					comet.keepOpen( 'touched' );
				},
				minHeight : '2em',
				position : {
					my : 'left bottom',
					at : 'left bottom',
					of : window
				},
				width : '60em',
				resizeStart: function ( event, ui ) {
					var pos = comet.$dialog.offset();
					if ( 0 )
					comet.$dialog.css( {
						position: 'absolute',
						top : pos.top,
						left : pos.left
					} );
					comet.keepOpen( 'touched' );
				},
				resize : function ( event, ui ) {
					//comet.fixScroll();
					if ( 0 ) {
					var pos = comet.$dialog.offset();
					comet.$dialog.css( {
						position: 'fixed',
						top : pos.top - $(window).scrollTop(),
						left : pos.left - $(window).scrollLeft()
					} );
					}
				},
				resizeStop : function ( event, ui ) {
					comet.fixScroll();
					var pos = comet.$dialog.offset();
					comet.$dialog.css( {
						position: 'fixed',
						top : pos.top - $(window).scrollTop(),
						left : pos.left - $(window).scrollLeft()
					} );
				},
				title : mw.message( 'ww-comet-dialog-title' ).plain()
			} );
			// at least in 1.21, that can't position it precisely, so
			this.$dialog = $( '.ww-comet-dialog-' + this.key );
			if ( CometAction.stack.length > 0 ) {
				var $other = CometAction.stack[ CometAction.stack.length - 1 ];
				var nextto = $other.offset();
				nextto = {
					top : nextto.top - $(window).scrollTop(),
					left : nextto.left - $(window).scrollLeft()
				};
				var top = nextto.top - $other.height() - 20;
				if ( top <= 10 ) {
					top = 10;
				}
				this.$dialog.css( {
					position : 'fixed',
					top : top,
					left : nextto.left
				} );
			} else {
				var pos = this.$dialog.offset();
				pos = {
					top : pos.top - $(window).scrollTop(),
					left : pos.left - $(window).scrollLeft()
				};
				this.$dialog.css( {
					position: 'fixed',
					top : pos.top - 20,
					left : 20
				} );
			}
			CometAction.stack.push( this.$dialog );
		},

		// keep a timer to force a reconnect if nothing happens for a while
		keepAlive : function () {
			if ( this.timerId ) {
				window.clearTimeout( this.timerId );
			}
			var comet = this;
			if ( ! this.reconnectInterval ) {
				this.reconnectInterval = mw.config.get( 'cometRetryInterval' ) || 10000;
				this.reconnectFunction = function () {
					//console.log( 'comet keepAlive timeout' );
					if ( comet.esource.readyState !== 1 ) {
						comet.appendMessage( mw.message( 'ww-timed-out-reconnecting' ).plain() );
						//comet.closeConnection();
						comet.connectToWiki();
					}
				};
			}
			this.timerId = window.setTimeout( this.reconnectFunction, this.reconnectInterval );
		},

		// if the user never touches the dialog, it'll fade away some time
		// after it finishes.  Here we keep track of that.
		// finished argument:
		//   false : cancel any scheduled close
		//   true : schedule a close, if appropriate
		//   'touched' : make sure it never closes
		// Semantics of keepOpenTimer:
		//   if true : the dialog has been touched, never close it
		//   if false : it hasn't been touched but it isn't finished
		//   else : it's untouched and finished, and is scheduled to close
		keepOpen : function ( finished ) {
			if ( this.keepOpenTimer === true ) {
				return;
			}
			if ( this.keepOpenTimer ) {
				window.clearTimeout( this.keepOpenTimer );
			}
		
			if ( finished === 'touched' ) {
				this.keepOpenTimer = true;
			} else if ( finished ) {
				var comet = this;
				this.keepOpenTimer = window.setTimeout(
					function () {
						comet.$dialog_div.dialog( {
							hide : 'fadeOut'
						} );
						comet.$dialog_div.dialog( "close" );
						// TODO: clicking on dialog while it's fading out should revive it
					},
					7000
				);
			} else {
				this.keepOpenTimer = false;
			}
		},

		// shut everything down when needed
		// note this sometimes doesn't communicate an abort to the server.
		// this is why the server times itself out pretty frequently.
		// this needs to work when called before init()
		closeConnection : function () {
			if ( this.state != 'idle' && this.done ) {
				this.done( this.apiCall, this.opts );
			}
			if ( this.timerId ) {
				window.clearTimeout( this.timerId );
			}
			if ( this.esource ) {
				this.esource.close();
			}
			this.state = 'idle';
		}
	};

	var sharedCometInstance;

	$.extend( mw.libs.ext.ww, {

		defaultCometOKFn : function ( result, apiCall, opts, comet ) {
			mw.hook( 'ww-api-' + apiCall.action + '-ok' ).fire( result, apiCall, opts );
		},

		defaultCometDoneFn : function ( apiCall, opts ) {
			mw.libs.ext.ww.defaultApiDoneFn( apiCall, opts );
		},

		defaultCometErrFn : function ( code, result, messages, apiCall, comet ) {
		},

		doComet : function ( apiCall, opts, comet ) {
			comet = comet || mw.libs.ext.ww.sharedComet();
			comet.init( apiCall, opts );
			comet.connectToWiki();
		},

		sharedComet : function () {
			if ( ! sharedCometInstance ) {
				sharedCometInstance = Object.create( CometAction ).init();
			}
			if ( sharedCometInstance.state !== 'idle' ) {
				return Object.create( CometAction ).init();
			}
			return sharedCometInstance;
		},

		cometSave : function ( $form ) {
			var spinnerName = 'ww-comet-spinner-' + CometAction.generateKey();
			mw.libs.ext.ww.injectTinySpinner( $( '#wpSave' ), spinnerName );
			var peRequest = {
				operation : {
					name : 'merge-session'
				},
				preview : mw.config.get( 'wwPreviewKey' )
			};
			var apiCall = {
				action : 'ww-pass-to-pe',
				request : JSON.stringify( peRequest )
			};
			comet = Object.create( CometAction );
			comet.appendText( mw.message( 'ww-comet-merging-from-preview' ).parse() );
			mw.libs.ext.ww.doComet(
				apiCall,
				{
					'done' : function () {
						mw.libs.ext.ww.removeTinySpinner( spinnerName );
					},
					'ok' : function () {
						$form.off( 'submit.ww-comet-merge' );
						$form.submit();
					}
				},
				comet
			);
		}

	} );

	// in case of previewing, set up merge during save
	$( function () {
		var notsaving = false;
		$( '#wpPreview, #wpDiff' ).click( function () {
			notsaving = true;
		} );
		$( '#editform' ).on( 'submit.ww-comet-merge', function ( event ) {
			if ( notsaving ) {
				return;
			}
			event.preventDefault();
			mw.libs.ext.ww.cometSave( $( this ) );
		} );
	} );
}

} )( jQuery, mw );
