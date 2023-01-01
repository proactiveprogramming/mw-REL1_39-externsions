( function ( mw, $ ) {

/**
 * code for dynamic retrieval of project data and autofilling destination.
 */
var ajaxProjectData = {
	// TODO: proactively supply project data in the HTML page?
	'responseCache': { '' : {} },

	/**
	 * Initiate an Ajax request for project data
	 *
	 * @param {string} projectname name of project
	 * @param {HTMLElement} input DOM element where spinner should go
	 * @param {function} callback callback function to be given project data
	 */
	'fetchProjectData': function( projectname, input, callback ) {
		// normalize project name
		if ( mw.config.get( 'wgCapitalizeUploads' ) ) {
			projectname = projectname.charAt(0).toUpperCase().concat( projectname.substring( 1, 10000 ) );
		}
		projectname = projectname.replace( / /g, '_' );
		// look for cache hit
		for ( var pn in this.responseCache ) {
			if ( pn == projectname ) {
				// is it possible for this code to be multithreaded?
				// if so, there's a race condition here:
				// multiple calls could fail this condition and go
				// on to set it to -1 and make the api call.
				// not end of world though, worst case is redundant
				// but harmless api calls
				if ( this.responseCache[projectname] == -1 ) {
					// postpone concurrent call
					window.setTimeout( function() {
						ajaxProjectData.fetchProjectData(
							projectname, input, callback );
					}, 500 );
				} else {
					callback( this.responseCache[projectname] );
				}
				return;
			}
		}
		// else request the data from the wiki
		this.responseCache[projectname] = -1;
		var spinnername = 'projectdata-'.concat( input.id );
		$( input ).injectSpinner( spinnername );

		// 1.19-style get(), for compatibility
		mw.loader.using( 'mediawiki.api', function() {
			( new mw.Api() ).get( {
				action: 'ww-get-project-data',
				project: projectname
			}, { 'ok' : function ( data ) {
				$.removeSpinner( spinnername );
				callback( data );
				ajaxProjectData.responseCache[projectname] = data;
			}, 'err' : function( code, details ) {
				mw.log( 'mw.Api error: ', code, details );
				$.removeSpinner( spinnername );
				delete ajaxProjectData.responseCache[projectname];
			} } );
		} );
	}
};

function fillProjFilename( fieldset, e ) {
	var pftouched = $( 'input#wpProjFilenameTouched'+fieldset.data('rowIndex') ).val();
	if ( pftouched ) {
		return; 
	}
	var sourcefilename = e.value;
	var slash = sourcefilename.lastIndexOf('/');
	var backslash = sourcefilename.lastIndexOf('\\');
	if ( slash < backslash ) {
		slash = backslash;
	}
	if ( slash == -1 ) {
		projfilename = sourcefilename;
	} else {
		projfilename = sourcefilename.substring( slash + 1, 1000 );
	}
	pf = fieldset.find( 'input.wpProjFilename' );
	pf.val( projfilename );
	pf.change();
}

function fillDestInfo( fieldset, e ) {
	fillingDestInfo = true;
	var i = fieldset.data('rowIndex');
	var dptouched = $( 'input#wpDestPageTouched'+i ).val();
	var dttouched = $( 'input#wpDestTypeTouched'+i ).val();
	if ( dptouched && dttouched ) {
		return;
	}
	var projectname = fieldset.find( 'input.wpProjectName' ).val();
	var filename = fieldset.find( 'input.wpProjFilename' ).val();
	if ( filename ) {
		// make the Ajax call
		// with the fieldset in a closure in case this gets overlapping calls
		(function( fieldset ) {
			ajaxProjectData.fetchProjectData( projectname, e, function( data ) {
				var type;
				if ( dttouched ) {
					type = fieldset.find( 'select.wpDestType' ).val();
				} else {
					if ( data && 'project-files' in data &&
						filename in data['project-files'] ) {
						var filedata = data['project-files'][filename];

						type = 'project';
						if ( 'source' in filedata ) {
							type = 'source';
						} else if ( 'archived' in filedata ) {
							type = 'archived';
						}
					} else {
						// TODO be smart about types when
						// a file is unknown to the project?
						type = 'source';
					}
					fieldset.find( 'select.wpDestType' )
						.val( type )
						.change();
				}
				if ( ! dptouched && type != 'project' ) {
					var page = mw.libs.ext.ww.suggestPage( 
						projectname,
						data,
						filename
					);
					fieldset.find( 'input.wpDestPage' )
						.val( page )
						.change();
				}
				fillingDestInfo = false;
			} );
		})( fieldset );
	} 
}

function maybePropagateProjectNameDownward( $fieldset, projinput ) {
	var projectname = projinput.value;
	var $lastfs = mw.libs.ext.multiupload.findLastRow();
	if ( $fieldset.data( 'rowIndex' ) == $lastfs.data( 'rowIndex') - 1
		&& mw.libs.ext.multiupload.isBlank( $lastfs ) ) {
		$lastfs.find( 'input.wpProjectName' ).val( projectname );
		mw.libs.ext.multiupload.templateUploadRow.find( 'input.wpProjectName' ).val( projectname );
	}
}

$( document ).ready( function () {
	var destCheckCache = { '': '' };

	var destCheck = function ( input, warningId, acks ) {
		for ( var cached in destCheckCache ) {
			if ( cached == input.value ) {
				window.wgUploadWarningObj.setWarning(
					destCheckCache[cached],
					warningId,
					acks
				);
				return;
			}
		}
		$.removeSpinner( input.id );
		$( input ).injectSpinner( input.id );
		// in MW 1.21 and before, old-style ajax call to query about existing files
		var tryOldAjaxCall = function () {
			var filename = input.value.replace( /^\w*:/, '' );
			if ( ! sajax_init_object() || filename == '' ) {
				return;
			}
			sajax_do_call( 
				'SpecialUpload::ajaxGetExistsWarning',
				[ filename ],
				function ( result ) {
					window.wgUploadWarningObj.processResult(
						result.responseText,
						filename,
						warningId,
						acks
					);
					$.removeSpinner( input.id );
				}
			);
		}
		// try 'iiprop' query, which works in MW 1.22+
		mw.loader.using( 'mediawiki.api', function() {
			( new mw.Api() ).get( {
				action: 'query',
				prop: 'imageinfo',
				iiprop: 'uploadwarning',
				titles: input.value,
				indexpageids: ''
			}, { 'ok' : function ( data ) {
				var resultInfo, resultOut = '';
				if ( data.query ) {
					resultInfo = data.query.pages[ data.query.pageids[0] ];
					if ( 'invalid' in resultInfo ) {
						resultOut = resultInfo.invalid;
					} else if ( 'imageinfo' in resultInfo ) {
						resultOut = resultInfo.imageinfo[0].html;
					}
				}
				if ( ! resultOut ) {
					// if failed, try old style ajax query
					tryOldAjaxCall();
				} else {
					destCheckCache[input.value] = resultOut;
					window.wgUploadWarningObj.setWarning( resultOut, warningId, acks );
					$.removeSpinner( input.id );
				}
			}, 'err' : function( code, details ) {
				mw.log( 'mw.Api error: ', code, details );
				$.removeSpinner( input.id );
				window.wgUploadWarningObj.setWarning( '', warningId, acks );
			} } );
		} );
	};
	ajaxUploadDestCheck = false;
	var oldIsBlank = mw.libs.ext.multiupload.isBlank;
	mw.libs.ext.multiupload.isBlank = function ( $fieldset ) {
		var $pfn = $fieldset.find( 'input.wpProjFilename' );
		return ( ! $pfn.val() && oldIsBlank( $fieldset ) );
	};
	var oldSetupRow = mw.libs.ext.multiupload.setupRow;
	mw.libs.ext.multiupload.setupRow = function ( i, $fieldset ) {
		if ( ! $fieldset ) {
			$fieldset = mw.libs.ext.multiupload.findRow( i );
		}
		$fieldset.find( 'input' ).off( 'change' );
		oldSetupRow( i, $fieldset );
		(function( $fieldset, i ) {
			var fillDestTimer, destCheckTimer;
			fillDestInfoInFuture = function( $fieldset, e ) {
				if ( fillDestTimer ) {
					window.clearTimeout( fillDestTimer );
				}
				fillDestTimer = window.setTimeout( function() {
					fillDestTimer = null;
					fillDestInfo( $fieldset, e );
				}, 500 );
			};
			// handler for when input is selected:
			// possibly update the destination filename
			// TODO smart checking against 2 file size limits
			// TODO does triggering these as we go do right?
			$fileinputs = $fieldset.find( 'input.wpUploadFile,input.wpUploadFileURL' )
				.change( function( event ) {
					fillProjFilename( $fieldset, event.target );
				} );
			// handler for when dest filename or project is updated:
			// update the destination page and type of project file
			$fieldset.find( 'input.wpProjFilename,input.wpProjectName' )
				.change( function( event ) {
					fillDestInfoInFuture( $fieldset, event.target );
				} );
			// Also, when project name is changed: maybe make it default for 
			// subsequent rows
			$fieldset.find( 'input.wpProjectName' )
				.change( function ( event ) {
					maybePropagateProjectNameDownward( $fieldset, event.target );
				} );
			// handler for when project name is updated by hand:
			// mark it 'touched' so it won't be autofilled
			// TODO when projectname is empty, don't allow DestType
			// to be 'project'
			// NOTE I used to use keypress but it doesn't catch
			// backspace on chrome
			$projnameinput = $fieldset.find( 'input.wpProjectName' ).keyup( function() {
					$( '#wpProjectNameTouched'+i ).val( 1 );
					$fieldset.find( 'label[for="wpProjectName'+i+'"]' )
						.css( 'color', '#404040' );
				} )
				.change( function( event ) {
					if ( event.target.value === '' ) {
						var sel = $fieldset.find( 'select.wpDestType' );
						if ( sel.val() == 'project' ) {
							sel.val( 'source' );
							sel.find( 'option[value="source"]' )
								.attr('selected', true);
						}
						// note this disable doesn't work in IE
						sel.find( 'option[value="project"]' ).prop('disabled', true);
						sel.change();
					} else {
						$fieldset.find( 'select.wpDestType option[value="project"]' ).prop('disabled', false);
					}
				} );
			// handler for when dest filename is updated by hand:
			// mark it 'touched'
			$projfilenameinput = $fieldset.find( 'input.wpProjFilename' ).keyup( function() {
					$( 'input#wpProjFilenameTouched'+i ).val( 1 );
					$fieldset.find( 'label[for="wpProjFilename'+i+'"]' )
						.css( 'color', '#404040' );
				} );
			// handler for when dest file type is updated by hand:
			// mark it 'touched'
			fillingDestInfo = true;
			$desttypeinput = $fieldset.find( 'select.wpDestType' )
				.change( function( event ) {
					var destPageIsRelevant = ( $(event.target).val() != 'project' );
					$fieldset.find( 'input.wpDestPage' )
						.prop( 'disabled', ! destPageIsRelevant )
						.change();
					// careful- don't recurse if it's being changed
					// by fillDestInfo()
					if (!fillingDestInfo) {
						$( 'input#wpDestTypeTouched'+i ).val(1);
						$fieldset.find( 'label[for="wpDestType'+i+'"]' )
							.css( 'color', '#404040' );
						fillDestInfoInFuture( $fieldset, event.target );
					}
				} );
			fillingDestInfo = false;
			// handler for when dest filename is updated by hand:
			// mark it 'touched', and check on the destination,
			// if it's a File: page.
			var $dp = $fieldset.find( 'input.wpDestPage' );
			var warningId = 'wpDestFile-warning'+i;
			var acks = document.getElementsByName( 'wpDestFileWarningAck'+i );
			if ( $.isEmpty( $fieldset.find( '#' + warningId ) ) ) {
				$fieldset.find( '.wpDestFile-warning' ).parent( 'tr' ).remove();
				$dp.parent().parent().after( '<tr><td id="'+warningId+'" class="wpDestFile-warning" colspan="2"/></tr>' );
			}
			$dp.keyup( function( event ) {
					$( '#wpDestPageTouched'+i ).val( 1 );
					$fieldset.find( 'label[for="wpDestPage'+i+'"]' )
						.css( 'color', '#404040' );
					if ( event.target.value.match(/^(File|Image|Media):/i) ) {
						if ( destCheckTimer ) {
							clearTimeout( destCheckTimer );
						}
						destCheckTimer = setTimeout( function () {
							destCheckTimer = null;
							destCheck( event.target, warningId, acks );
						}, 500 );
					} else {
						// and do something for text files?
						window.wgUploadWarningObj.setWarning(
							'', warningId, acks);
					}
				} )
				.change( function( event ) {
					var page = event.target.value;
					if ( !$dp.prop('disabled') && page.match(/^(File|Image|Media):/) ) {
						// check for existing File:
						destCheck( event.target, warningId, acks);
					} else {
						// and do something for text files?
						window.wgUploadWarningObj.setWarning(
							'', warningId, acks);
					}
				} );
			// Go ahead and process the current values in the row's fields
			$fileinputs.change();
			$projfilenameinput.change();
			$projnameinput.change(); 
			$desttypeinput.change();
			$dp.change(); 
		})( $fieldset, i );
	};
	var wpFirstRowIndex = mw.config.get( 'wpFirstRowIndex' );
	var wpLastRowIndex = mw.config.get( 'wpLastRowIndex' );
	window.setTimeout( function() {
		mw.libs.ext.multiupload.captureTemplate();
		for (var i = wpFirstRowIndex; i <= wpLastRowIndex; ++i ) {
			window.uploadSetupByIds( 
				'wpSourceType'+i+'url', 
				'wpUploadFileURL'+i, 
				null, // 'wpLicense',
				'wpDestFile-warning'+i, 
				'wpDestFileWarningAck'+i, // TODO
				null, // destFileId // TODO reinstate DestCheck fnlty
				'mw-htmlform-row-'+i, 
				null // 'mw-license-preview'+i 
			);

			mw.libs.ext.multiupload.setupRow( i );
		}
		var form = $('#mw-upload-form');
		mw.libs.ext.multiupload.maybeAddBlankRow( form );
		mw.libs.ext.multiupload.revealForm();
		form.css('background-image', 'none');
	}, 100 );
} ); 

}( mediaWiki, jQuery ) );
