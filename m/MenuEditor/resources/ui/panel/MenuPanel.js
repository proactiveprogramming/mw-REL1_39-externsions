ext.menueditor.ui.panel.MenuPanel = function ( cfg, treeData, menuType ) {
	ext.menueditor.ui.panel.MenuPanel.parent.call( this, cfg );

	this.pagename = cfg.pagename;
	this.allowEdit = cfg.mode && cfg.mode === 'edit';
	var classname = ext.menueditor.util.callbackFromString(
		ext.menueditor.registry.menu.registry[ menuType ]
	);

	if ( typeof treeData === 'undefined' && !this.allowEdit ) {
		this.$element.append(
			new OO.ui.MessageWidget( {
				type: 'warning',
				label: mw.message( 'menueditor-ui-redlink-notice' ).text()
			} ).$element
		);
		return;
	}

	// eslint-disable-next-line new-cap
	this.tree = new classname( {
		data: treeData || cfg.defaultData || [],
		editable: this.allowEdit
	} );

	if ( this.allowEdit ) {
		mw.loader.using( [ 'ext.menuEditor.toolbar' ] ).done( function () {
			var menuToolbar = new ext.menueditor.ui.widget.MenuToolbar();
			this.$element.prepend( menuToolbar.toolbar.$element );

			menuToolbar.toolbar.connect( this, {
				newItem: function () {
					this.tree.addSubnode();
				},
				cancel: function () {
					this.emit( 'cancel' );
				},
				save: 'saveEdit'
			} );
		}.bind( this ) );
	}

	this.$element.append( this.tree.$element );
};

OO.inheritClass( ext.menueditor.ui.panel.MenuPanel, OO.ui.PanelLayout );

ext.menueditor.ui.panel.MenuPanel.prototype.getTree = function () {
	return this.tree;
};

ext.menueditor.ui.panel.MenuPanel.prototype.saveEdit = function () {
	var data = this.tree.getNodes();

	ext.menueditor.api.save( this.pagename, data ).done( function () {
		this.emit( 'saveSuccess' );
	}.bind( this ) ).fail( function ( error ) {
		this.emit( 'saveFailed', error );
	}.bind( this ) );
};
