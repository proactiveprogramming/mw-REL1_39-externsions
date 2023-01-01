/**
 * ext.workingwiki.suggestions.js
 *
 * functions for suggesting where a WW file should be placed.
 * used by both Special:ImportProjectFiles and Special:ManageProject.
 */

( function( mw, $ ) {

  /**
   * suggestPage()
   * propose a page location for storing a given filename in a project
   *
   * assumes project data is known, but could also be made to load it
   * by ajax or something.
   *
   * requires a bit of global data:
   * mw.config.get('wgCapitalizeUploads') [provided by MW]
   * mw.config.get('wgImageNamespace') [added by WW]
   * and one of
   * mw.config.get('wwImportTextExtensions') [provided by WW if import-as-image is default]
   * mw.config.get('wwImportImageExtensions') [provided by WW if import-as-text is default]
   *
   * @param {string} projectname Name of project, assumed to be normalized
   * @param {object} projectdata The project data, in the format returned
   *   by the ww-get-project-data API call
   * @param {string} filename The filename to be placed
   */
mw.libs.ext.ww.suggestPage = function( projectname, projectdata, filename ) {
	if ( projectdata && 'project-files' in projectdata &&
		filename in projectdata['project-files'] ) {
		var filedata = projectdata['project-files'][filename];
		if ( filedata ) {
			if ( 'source' in filedata && 'page' in filedata ) {
				return filedata.page;
			} else if ( 'archived' in filedata ) {
				for ( var arch in filedata.archived ) {
					return arch; // the first entry will do
				}
			} else if ( 'appears' in filedata ) {
				for ( var app in filedata.appears ) {
					return app;
				}
			}
		}
	}
		// if none of these, construct the default suggestion.
	var dot = filename.lastIndexOf('.');
	var image = false;
	if (dot != -1) {
		var ext = filename.substring(dot+1,10000).toLowerCase();
		var exts;
		// either text or image extensions is set, default is
		// whichever isn't set.
		// TODO is it better to set both, auto-detect binary content
		// if extension isn't recognized
		if ( ( exts = mw.config.get( 'wwImportTextExtensions' ) ) ) {
			if ( $.inArray( ext, exts ) === -1 ) {
				image = true; 
			}
		} else if ( ( exts = mw.config.get( 'wwImportImageExtensions' ) ) ) {
			if ( $.inArray( ext, exts ) !== -1 ) {
				image = true;
			}
		}
	}
	var page;
	if (projectname !== '') {
		if (image)
			page = projectname + '/' + filename;
		else
			page = projectname;
	} else {
		page = filename;
	}
	if ( mw.config.get( 'wgCapitalizeUploads' ) ) { 
		page = page.charAt(0).toUpperCase() + page.substring(1,10000);
	}
	if (image) {
		page = wgImageNamespace + ':' + page.replace(/[\/:]/g, '$');
	}
	return page.replace(/ /g, '_');
};

})( mediaWiki, jQuery );
