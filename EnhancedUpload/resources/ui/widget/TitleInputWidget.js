window.enhancedUpload = window.enhancedUpload || {};
window.enhancedUpload.ui.widget = window.enhancedUpload.ui.widget || {};

enhancedUpload.ui.widget.TitleInputWidget = function ( cfg ) {
	cfg.namespace = 6;
	cfg.icon = 'search';
	enhancedUpload.ui.widget.TitleInputWidget.parent.call( this, cfg );
};

OO.inheritClass( enhancedUpload.ui.widget.TitleInputWidget, mw.widgets.TitleInputWidget );

enhancedUpload.ui.widget.TitleInputWidget.prototype.onLookupMenuChoose = function ( item ) {
	enhancedUpload.ui.widget.TitleInputWidget.super.prototype.onLookupMenuChoose.call( this, item );

	this.emit( 'choose' );
};

// due to compatibility with NSFileRepo File:User:filename.jpg - this is not working
// without changing creation of title
enhancedUpload.ui.widget.TitleInputWidget.prototype.getMWTitle = function ( value ) {
	var title = value !== undefined ? value : this.getQueryValue(), titleObj = null;
	if ( title.length > 0 ) {
		// mw.Title doesn't handle null well
		titleObj = mw.Title.makeTitle( this.namespace, title );
	}

	return titleObj;
};
