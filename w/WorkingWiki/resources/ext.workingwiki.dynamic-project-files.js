( function ( $, mw ) {

var spinnerName = 'dynamic-project-file-spinner';

function emptyAltLinks() {
	return $( '<span class="ww-altlinks"><span class="ww-altlinks-open-bracket">[</span><span class="ww-altlinks-inner"/><span class="ww-altlinks-close-bracket">]</span></span>' );
}

function makeLink( title, params ) { // this isn't needed in 1.23 but is in 1.21
	var link = title.getUrl();
	for ( var k in params ) {
		if ( params[k] !== undefined && params[k] !== null ) {
			if ( /\?/.test( link ) ) {
				link += '&';
			} else { 
				link += '?';
			}
			link += encodeURIComponent( k ) + '=' + encodeURIComponent( params[k] );
		}
	}
	return link;
}

function addDynamicLinksToAltlinks( $fs, apiCall ) {
	// this code assumes we're using the pulldown styling for altlinks menu
	var $ai = $fs.find( '.ww-altlinks-inner' );
	
	if ( $ai.has( '.ww-altlinks-download' ).length === 0 ) {
		$ai.prepend( 
			$( '<span/>' )
				.addClass( 'ww-altlink ww-altlinks-download' )
				.html( $( '<a>' )
					.attr(
						'href',
					       	makeLink(
							new mw.Title( 'GetProjectFile', -1 ),
							{
								'project' : apiCall.project,
								'resources' : apiCall.resources,
								'preview-key' : apiCall['preview-key'],
								'background-job' : apiCall['background-job'],
								'display' : 'download',
								'make' : 0,
								'filename' : apiCall.filename,
							}
						)
					)
					.text( mw.message( 'ww-dynamic-altlinks-download' ).plain() )
				)
		);
	}

	if ( ! apiCall.resources && ! apiCall['background-job'] &&
	     $ai.has( '.ww-dynamic-altlinks-remake' ).length === 0
	 ) {
		$ai.prepend(
			$( '<span/>' )
				.addClass( 'ww-altlink ww-write-action ww-dynamic-altlinks-remake' )
				.html( $( '<a>' )
					.attr(
						'href',
					       	makeLink(
							new mw.Title( 'GetProjectFile', -1 ),
							{
								'project' : apiCall.project,
								'make' : 1,
								'resources' : apiCall.resources,
								'preview-key' : apiCall['preview-key'],
								'background-job' : apiCall['background-job'],
								'display' : apiCall.display,
								'filename' : apiCall.filename,
							}
						)
					)
					.text( mw.message( 'ww-dynamic-altlinks-remake' ).plain() )
				)
		);
	}
	$ai.find( '.ww-dynamic-altlinks-remake a' )
		.click( function ( event ) {
			event.preventDefault();
			apiCall.make = 1;
			mw.libs.ext.ww.injectTinySpinner( $( event.target ), spinnerName );
			// relax, don't do it when you want to comet
			loadProjectFile( $fs, apiCall, mw.config.get( 'wwUseComet' ) );
		} );

	if ( $ai.has( '.ww-dynamic-altlinks-reload' ).length === 0 ) {
		$ai.prepend( 
			$( '<span/>' )
				.addClass( 'ww-altlink ww-dynamic-altlinks-reload' )
				.html( $( '<a>' )
					.attr(
						'href',
					       	makeLink(
							new mw.Title( 'GetProjectFile', -1 ),
							{
								'project' : apiCall.project,
								'resources' : apiCall.resources,
								'preview-key' : apiCall['preview-key'],
								'display' : apiCall.display,
								'make' : 0,
								'filename' : apiCall.filename,
							}
						)
					)
					.text( mw.message( 'ww-dynamic-altlinks-reload' ).plain() )
				)
		);
	}
	$ai.find( '.ww-dynamic-altlinks-reload a' )
		.click( function ( event ) {
			event.preventDefault();
			apiCall.make = 0;
			mw.libs.ext.ww.injectTinySpinner( $( event.target ), spinnerName );
			loadProjectFile( $fs, apiCall, mw.config.get( 'wwUseComet' ) );
		} );

	if ( $ai.has( '.ww-dynamic-altlinks-filename' ).length === 0 ) {
		$ai.prepend( 
			$( '<span/>' )
				.addClass( 'ww-altlink ww-dynamic-altlinks-filename' )
				.html( $( '<span>' )
					.text( apiCall.filename )
				)
		);
	}

	$ai.parent( '.ww-altlinks.ww-write-only' ).removeClass( 'ww-write-only' );
	return $fs;
}

function replaceAltlinks( $fs, $altlinks ) {
	var $oal = $fs.find( '.ww-altlinks' ).first();
	//$oal.slice(1).remove(); // temporary for mis-handled links in wikitext
	if ( ! $.isEmpty( $oal ) ) {
		// note, a .wikitext file can yield html with
		// multiple altlinks spans in it.  i'm probably not
		// handling them correctly.
		$oal.replaceWith( $altlinks );
	} else {
		$altlinks.insertBefore( $oal );
	}
	return $fs;
}

var mathJaxCounter = 0;

var loadInProgress = 0;

function loadProjectFileCallback( data, apiCall, opts, $container, $altlinks )  {
	if ( 'styles' in data ) {
		mw.loader.load( data.styles );
	}
	if ( 'scripts' in data ) {
		mw.loader.load( data.scripts );
	}
	if ( 'modules' in data ) {
		mw.loader.load( data.modules );
	}
	if ( 'headItems' in data ) {
		var headtext = '';
		for ( var k in data.headItems ) {
			headtext += data['headItems'][k];
		}
		$( 'head' ).append( $( headtext ) );
	}
	var text;
	if ( 'text' in data ) {
		text = data.text;
	} else if ( 'text-base64' in data ) {
		text = atob( data['text-base64'] );
	} else {
		text = '';
	}
	var messages;
	if ( apiCall.action in data && data[ apiCall.action ].messages ) {
		messages = data[ apiCall.action ].messages;
	} else {
		messages = '';
	}
	var $text;
	if ( text ) {
		$text = $.parseHTML( text );
	} else {
		$text = emptyAltLinks();
	}
	var $newcon = $( '<span>' + messages + '</span>' )
		.addClass( 'ww-dynamic-project-file ww-dynamic-project-file-processed' )
		.append( $text );
	// workaround for annoying firefox bug, to make it update images
	$newcon.find( 'img' ).attr( 'src', function ( i, src ) {
		return src.replace(
			/\bfilename=/i, 
			'random-number=' + Math.floor(Math.random()*1000) + '&filename='
		);
	} );
	$container.replaceWith( $newcon );
	replaceAltlinks( $newcon, $altlinks );
	addDynamicLinksToAltlinks( $newcon, apiCall );
	if ( ! $.isEmpty( $newcon.find( 'math' ) ) && window.MathJax && MathJax.Hub ) {
		++mathJaxCounter;
		var mjid = 'ww-mathjax-' + mathJaxCounter;
		$newcon.wrap(
			$( '<span>' ).attr( 'id', mjid )
		);
		MathJax.Hub.Queue( [ 'Typeset', MathJax.Hub, mjid ] );
	}
	mw.libs.ext.ww.fixUpProjectFiles( $newcon );
	//$container = $newcon;
}

function loadProjectFileErrorFn( code, result, messages, api, $container, $altlinks ) {
	if ( code != 'http' ) {
		code = mw.message(
			'ww-dynamic-project-file-failed',
			api.filename,
			api.project
		).text();
	}
	var $errdiv = mw.libs.ext.ww.assembleMessages( result )
		.prepend( $( '<span/>' ).text( mw.libs.ext.ww.extractErrorText( code, result ) ) );
	var $messages = $errdiv.find( '.ww-messages' );
	if ( ! $.isEmpty( $messages ) ) {
		var $newfs = $( '<div/>' )
			.append(
				$messages.addClass( 'ww-dynamic-project-file ww-dynamic-project-file-processed ww-project-file-error' )
			);
		replaceAltlinks( $newfs, $altlinks );
		addDynamicLinksToAltlinks( $newfs, api );
		$container.replaceWith( $newfs );
		$container = $newfs;
	} else {
		var $newcon = $( '<div/>' )
			.addClass( 'ww-dynamic-project-file ww-dynamic-project-file-processed' )
			.append( 
			$( '<fieldset/>' )
				.addClass( 'ww-messages ww-project-file-error' )
				.append(
					$( '<legend/>' )
						.text( mw.message( 'ww-messages-legend' ).text() )
				)
				.append( emptyAltLinks() )
				.append( $errdiv )
			);
		replaceAltlinks( $newcon, $altlinks );
		addDynamicLinksToAltlinks( $newcon, api );
		$container.replaceWith( $newcon );
		$container = $newcon;
	}
	mw.libs.ext.ww.defaultApiErrFn( code, result, messages, api );
}

function notifyIfDoneLoading( comet ) {
	if ( $.isEmpty( $( '.ww-dynamic-project-file-unprocessed' ) )
		&& ! $.isEmpty( $( 'div#footer' ) )
		&& comet && comet.appendMessage ) {
		comet.appendMessage( mw.message(
			'ww-comet-done-loading-files-in-page'
		).plain() );
	}
}

function loadProjectFile( $container, api, cometContext ) {
	if ( loadInProgress ) {
		return;
	}
	if ( console && console.log ) {
		var msg = 'loadProjectFile: ';
		if ( api )
			msg += api.filename;
		else if ( $container.data( 'filename' ) )
			msg += $container.data( 'filename' );
		console.log( msg );
	}
	++loadInProgress;
	var $altlinks;
       	if ( $container.data( 'altlinks' ) ) {
		$altlinks = $( $container.data( 'altlinks' ) );
	} else {
		$altlinks = $container.find( '.ww-altlinks' ).first();
	}
	if ( $.isEmpty( $altlinks ) ) {
		$altlinks = emptyAltLinks();
	}

	mw.libs.ext.ww.injectTinySpinnerInProjectFile( $container, spinnerName );
	$container.removeClass( 'ww-dynamic-project-file-unprocessed' )
		.addClass( 'ww-dynamic-project-file-processed' );
	if ( ! api ) {
		var title = new mw.Title(
			mw.config.get( 'wgTitle' ),
			mw.config.get( 'wgNamespaceNumber' )
		);
		api = {
			'action' : 'ww-get-project-file',
			'project' : $container.data( 'project' ),
			'filename' : $container.data( 'filename' ),
			'source-file' : $container.data( 'source' ),
			'make' : $container.data( 'make' ),
			'resources' : $container.data( 'resources' ),
			'containing-page' : title.getPrefixedText(),
			'display' : $container.data( 'display' ),
			//'altlinks' : JSON.stringify( $container.data( 'altlinks' ) ),
			'tag-args' : JSON.stringify( $container.data( 'tagArgs' ) ),
			'html' : 1
		};
		var previewKey = mw.config.get( 'wwPreviewKey' );
		if ( previewKey ) {
			api['preview-key'] = previewKey;
		}
		var bgKey = mw.config.get( 'wwBackgroundJob' );
		if ( bgKey ) {
			api['background-job'] = bgKey;
		}
	}
	var ajaxTimeout = (mw.config.get( 'wwTimeLimitForMakeJobs' ) + 120) * 1000;
	var apiOpts = {
		'ajax' : {
			'timeout' : ajaxTimeout 
		},
		'spinnerId' : spinnerName,
		'ok' : function ( data, api, opts ) {
			loadProjectFileCallback( data, api, opts, $container, $altlinks );
			notifyIfDoneLoading( cometContext );
		},
		'err' : function ( code, result, messages, api ) {
			loadProjectFileErrorFn( code, result, messages, api, $container, $altlinks );
			notifyIfDoneLoading( cometContext );
		},
		'done' : function ( api, opts ) {
			mw.libs.ext.ww.defaultApiDoneFn( api, opts );
			--loadInProgress;
			if ( console && console.log ) {
				console.log( 'loadProjectFile done: ' + api.filename );
			}
			setTimeout( function () {
				mw.libs.ext.ww.restartLoadingIfNeeded( cometContext );
			}, 100 );
		}
	};
	mw.libs.ext.ww.api( api, apiOpts, cometContext );
}

$.extend( mw.libs.ext.ww, {

	restartLoadingIfNeeded : function ( cometContext ) {
		// just for safety in pages without qpf calls
		$( '.ww-dynamic-project-file-unprocessed' ).each( function ( index, element ) {
			qpf( element );
		} );
		if ( loadInProgress ) {
			return;
		}
		// in case of preview: sync before making!
		var syncs = mw.config.get( 'wwSourceFilesToSync' );
		if ( syncs ) {
			if ( cometContext && cometContext.appendText ) {
				cometContext.appendText( mw.message(
					'ww-comet-syncing-source-files',
					Object.keys( syncs ).map( this.shortProjectName ).join( ', ' ),
					Object.keys( syncs ).length
				).parse() );
			}
			var request = {
				operation : {
					name : 'sync'
				},
				projects : {
				},
				'okay-to-create-preview-session' : true
			};
			for ( var s in syncs ) {
				request[ 'projects' ][ s ] = {
					'source-file-contents' : syncs[s],
					'short-dir' : this.shortProjectName( s )
				};
			}
			var previewKey = mw.config.get( 'wwPreviewKey' );
			if ( previewKey ) {
				request['preview'] = previewKey;
			}
			var bgKey = mw.config.get( 'wwBackgroundJob' );
			if ( bgKey ) {
				api['background-job'] = bgKey;
			}
			mw.config.set( 'wwSourceFilesToSync', false );
			++loadInProgress;
			mw.libs.ext.ww.api(
				{
					action : 'ww-pass-to-pe',
					request : JSON.stringify( request ),
				},
				{
					type : 'POST',
					ok : function () {
						--loadInProgress;
						mw.libs.ext.ww.restartLoadingIfNeeded( cometContext );
					}
				},
				cometContext
			);
		} else if ( mw.libs.ext.ww.projectFileQueue.length > 0 ) {
			loadProjectFile( mw.libs.ext.ww.projectFileQueue.shift(), undefined, cometContext );
		}
	},

	injectTinySpinnerWithin : function ( elt, id ) {
		//this.removeTinySpinner( id );
		return elt.append( this.createTinySpinner( id ) );
	},

	injectTinySpinnerInProjectFile : function ( $container, spinnerName ) {
		var $spinneree = $container.find( 'legend > span' );
		if ( $.isEmpty( $spinneree ) ) {
			$spinneree = $container.find( '.title' ).first();
		}
		if ( $.isEmpty( $spinneree ) ) {
			$spinneree = $container.find( '.ltx_title' ).first();
		}
		if ( $.isEmpty( $spinneree ) ) {
			$spinneree = $container;
		}
		mw.libs.ext.ww.injectTinySpinnerWithin(
			$spinneree,
			spinnerName
		);
	},

	addDynamicLinksToAltlinks : addDynamicLinksToAltlinks

} );

var pageLoadingContext;

// the comet object needs to be created before we start the api, so that
// we can display an initiating message.
if ( mw.config.get( 'wwUseComet' ) ) {
	// using() runs asynchronously - this is a synchronous load.
	mw.loader.load( 'ext.workingwiki.comet', undefined, true );
	pageLoadingContext = mw.libs.ext.ww.sharedComet();
}

// start the loading now
setTimeout( function () {
	mw.libs.ext.ww.restartLoadingIfNeeded( pageLoadingContext );
}, 10 );

// and call again when page is loaded, in case new placeholders have been added
$( function () {
	mw.libs.ext.ww.restartLoadingIfNeeded( pageLoadingContext );
} );

} )( $, mw );
