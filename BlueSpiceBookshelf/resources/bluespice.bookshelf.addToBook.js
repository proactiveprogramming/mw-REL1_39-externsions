$(document).on( 'click', '#ca-bookshelf-add-to-book', function( e ) {
	e.preventDefault();

	var me = this;
	mw.loader.using( 'ext.bluespice.bookshelf.addToBook.implementation' ).done( function() {
		var dialog = new bs.bookshelf.dialog.AddToBook( {
			pagename: mw.config.get( 'wgPageName' )
		} );
		dialog.show().closed.then( function( data ) {
			if ( data.needsReload ) {
				window.location.reload();
			}
		}.bind( me ) );
	} );

	return false;
} );
