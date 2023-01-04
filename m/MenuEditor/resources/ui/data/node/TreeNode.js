ext.menueditor.ui.data.node.TreeNode = function ( cfg ) {
	this.nodeData = cfg.nodeData;
	this.allowEdits = cfg.allowEdits || false;
	cfg.label = this.labelFromData( this.nodeData );
	ext.menueditor.ui.data.node.TreeNode.parent.call( this, cfg );

	this.$element.attr( 'data-type', this.nodeData.type );
	this.$element.attr( 'data-level', cfg.level );
};

// eslint-disable-next-line no-undef
OO.inheritClass( ext.menueditor.ui.data.node.TreeNode, OOJSPlus.ui.data.tree.Item );

// eslint-disable-next-line no-unused-vars
ext.menueditor.ui.data.node.TreeNode.prototype.labelFromData = function ( data ) {
	return '';
};

// eslint-disable-next-line no-unused-vars
ext.menueditor.ui.data.node.TreeNode.prototype.getIcon = function ( data ) {
	return '';
};

ext.menueditor.ui.data.node.TreeNode.prototype.getForm = function () {
	var dfd = $.Deferred();
	mw.loader.using( [ 'ext.forms.standalone' ], function () {
		// The following messages are used here
		// * menueditor-ui-menu-wiki-link-label
		// * menueditor-ui-menu-two-fold-link-spec-label
		// * menueditor-ui-menu-raw-text-label
		// * menueditor-ui-menu-keyword-label
		var labelText = mw.message( 'menueditor-ui-' + this.nodeData.type + '-label-edit' ).text();

		var form = new mw.ext.forms.standalone.Form( $.extend( {
			data: this.getNodeData(),
			definition: {
				items: [
					{
						type: 'section_label',
						title: labelText
					},
					{
						type: 'text',
						name: 'type',
						hidden: true
					}
				].concat( this.getFormFields() ),
				buttons: []
			},
			errorReporting: false,
			showTitle: false
		}, this.getFormConfig() ) );
		form.render();
		form.$element.addClass( 'menueditor-menu-node-form' );

		dfd.resolve( form );
	}.bind( this ), function () {
		// eslint-disable-next-line no-console
		console.error( 'Cannot load Forms framework' );
		dfd.reject();
	} );

	return dfd.promise();
};

ext.menueditor.ui.data.node.TreeNode.prototype.getFormFields = function () {
	// STUB
	return [];
};

ext.menueditor.ui.data.node.TreeNode.prototype.getFormConfig = function () {
	// STUB
	return {};
};

ext.menueditor.ui.data.node.TreeNode.prototype.possiblyAddOptions = function () {
	ext.menueditor.ui.data.node.TreeNode.parent.prototype.possiblyAddOptions.call( this );

	if ( !this.allowEdits ) {
		return;
	}
	var editButton = new OO.ui.ButtonWidget( {
		framed: false,
		label: mw.message( 'menueditor-ui-edit-node-label' ).text(),
		icon: 'edit'
	} );
	editButton.connect( this, {
		click: 'onEdit'
	} );
	// No idea why some random margin is added by default
	editButton.$element.css( { 'margin-left': 0 } );
	this.optionsPanel.$element.prepend( editButton.$element );
};

ext.menueditor.ui.data.node.TreeNode.prototype.onEdit = function () {
	this.tree.editNode( this );
};

ext.menueditor.ui.data.node.TreeNode.prototype.getNodeData = function () {
	var node = $.extend( {}, this.nodeData );
	delete ( node.items );
	delete ( node.name );
	return node;
};

ext.menueditor.ui.data.node.TreeNode.prototype.updateData = function ( data ) {
	this.nodeData = $.extend( this.nodeData, data );
	this.label = this.labelFromData( this.nodeData );
	this.labelWidget.setLabel( this.label );
	this.$element.attr( 'data-type', this.nodeData.type );
};

ext.menueditor.ui.data.node.TreeNode.prototype.shouldRender = function () {
	return true;
};
