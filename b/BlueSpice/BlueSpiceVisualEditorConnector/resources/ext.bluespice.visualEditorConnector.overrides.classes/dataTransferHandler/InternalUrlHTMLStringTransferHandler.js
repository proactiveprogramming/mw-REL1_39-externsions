/*!
 * BlueSpiceVisualEditorConnector UserInterface InternalUrlHTMLStringTransferHandler class.
 *
 * Heavily inspired by VisualEditor UserInterface UrlStringTransferHandler class.
 *
 * @copyright 2019 Hallo Welt! GmbH
 */

/**
 * Handle pastes of html code containing links.
 *
 * @class
 * @extends bs.vec.ui.InternalUrlHTMLStringTransferHandler
 *
 * @constructor
 * @param {ve.ui.Surface} surface
 * @param {ve.ui.DataTransferItem} item
 */
bs.vec.ui.InternalUrlHTMLStringTransferHandler = function BsVecUiInternalUrlHTMLStringTransferHandler() {
	// Parent constructor
	bs.vec.ui.InternalUrlHTMLStringTransferHandler.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( bs.vec.ui.InternalUrlHTMLStringTransferHandler, ve.ui.HTMLStringTransferHandler );

/* Static properties */
bs.vec.ui.InternalUrlHTMLStringTransferHandler.static.name = 'htmlString';

bs.vec.ui.InternalUrlHTMLStringTransferHandler.static.types = ['text/html', 'application/xhtml+xml'];

bs.vec.ui.InternalUrlHTMLStringTransferHandler.static.handlesPaste = true;

/**
 * RegExp matching an external url.
 * @property {RegExp}
 * @private
 */
bs.vec.ui.InternalUrlHTMLStringTransferHandler.static.urlRegExp = null; // Initialized below

ve.init.Platform.static.initializedPromise.then( function () {} );

bs.vec.ui.InternalUrlHTMLStringTransferHandler.static.matchFunction = function( item ) {
	// Do not handle table data
	var regex = new RegExp( '\<table.*\<\/table\>$' );
	return !regex.test( item.stringData || '' );
};

/**
 * @inheritdoc
 */
bs.vec.ui.InternalUrlHTMLStringTransferHandler.prototype.process = function () {
	var commentRegex = /<!--.*?-->/gms,
		text = this.item.getAsString();
	text = text.replace( commentRegex, '' );
	let newRules = {
		external: {
			blacklist: [
				// Annotations
				'textStyle/span', 'textStyle/font', 'textStyle/underline', 'meta/language', 'textStyle/datetime',
				// Nodes
				'article', 'section', 'div', 'alienInline', 'alienBlock', 'comment'
			],
			htmlBlacklist: {
				remove: ['sup.reference:not( [typeof] )', 'o:p'],
				unwrap: ['fieldset', 'legend']
			},
			removeOriginalDomElements: true,
			nodeSanitization: true
		},
		all: null
	};
	let doc = this.surface.getModel().getDocument().newFromHtml( text, newRules );
	this.resolve( doc );

};

/* Registration */
ve.ui.dataTransferHandlerFactory.register( bs.vec.ui.InternalUrlHTMLStringTransferHandler );
