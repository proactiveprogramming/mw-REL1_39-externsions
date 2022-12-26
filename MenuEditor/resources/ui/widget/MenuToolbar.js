ext.menueditor.ui.widget.MenuToolbar = function () {

	this.toolFactory = new OO.ui.ToolFactory();
	this.toolGroupFactory = new OO.ui.ToolGroupFactory();
	this.toolbar = new OO.ui.Toolbar( this.toolFactory, this.toolGroupFactory );

	this.addNewItemTool();

	this.toolbar.setup( [
		{
			name: 'new',
			type: 'bar',
			include: [ 'newItem' ]
		},
		{
			name: 'actions',
			type: 'bar',
			classes: [ 'toolbar-actions' ],
			include: [ 'cancel', 'save' ]
		}
	] );

	this.toolbar.initialize();
	this.toolbar.emit( 'updateState' );
};

ext.menueditor.ui.widget.MenuToolbar.prototype.addNewItemTool = function () {
	this.toolFactory.register( ext.menueditor.ui.widget.NewItemTool );
	this.toolFactory.register( ext.menueditor.ui.widget.CancelTool );
	this.toolFactory.register( ext.menueditor.ui.widget.SaveTool );
};

/** new item tool to create new menu widget */
ext.menueditor.ui.widget.NewItemTool = function () {
	ext.menueditor.ui.widget.NewItemTool.super.apply( this, arguments );
};

OO.inheritClass( ext.menueditor.ui.widget.NewItemTool, OO.ui.Tool );
ext.menueditor.ui.widget.NewItemTool.static.name = 'newItem';
ext.menueditor.ui.widget.NewItemTool.static.icon = 'add';
ext.menueditor.ui.widget.NewItemTool.static.title = mw.message( 'menueditor-toolbar-add' );
ext.menueditor.ui.widget.NewItemTool.static.label = mw.message( 'menueditor-toolbar-add' );
ext.menueditor.ui.widget.NewItemTool.static.displayBothIconAndLabel = true;

ext.menueditor.ui.widget.NewItemTool.prototype.onSelect = function () {
	this.setActive( false );
	this.toolbar.emit( 'newItem' );
	this.toolbar.emit( 'updateState' );
};
ext.menueditor.ui.widget.NewItemTool.prototype.onUpdateState = function () {};

/** cancel tool to cancel edit menu tree */
ext.menueditor.ui.widget.CancelTool = function () {
	ext.menueditor.ui.widget.CancelTool.super.apply( this, arguments );
};
OO.inheritClass( ext.menueditor.ui.widget.CancelTool, OO.ui.Tool );
ext.menueditor.ui.widget.CancelTool.static.name = 'cancel';
ext.menueditor.ui.widget.CancelTool.static.icon = 'cancel';
ext.menueditor.ui.widget.CancelTool.static.flags = [ 'destructive' ];
ext.menueditor.ui.widget.CancelTool.static.title = mw.message( 'menueditor-toolbar-cancel' );

ext.menueditor.ui.widget.CancelTool.prototype.onSelect = function () {
	this.setActive( false );
	this.toolbar.emit( 'cancel' );
	this.toolbar.emit( 'updateState' );
};
ext.menueditor.ui.widget.CancelTool.prototype.onUpdateState = function () {};

/** save tool to save menu tree */
ext.menueditor.ui.widget.SaveTool = function () {
	ext.menueditor.ui.widget.SaveTool.super.apply( this, arguments );
};

OO.inheritClass( ext.menueditor.ui.widget.SaveTool, OO.ui.Tool );
ext.menueditor.ui.widget.SaveTool.static.name = 'save';
ext.menueditor.ui.widget.SaveTool.static.icon = '';
ext.menueditor.ui.widget.SaveTool.static.title = mw.message( 'menueditor-toolbar-submit' );
ext.menueditor.ui.widget.SaveTool.static.flags = [ 'primary', 'progressive' ];
ext.menueditor.ui.widget.SaveTool.static.displayBothIconAndLabel = true;
ext.menueditor.ui.widget.SaveTool.prototype.onSelect = function () {
	this.setActive( false );
	this.toolbar.emit( 'save' );
	this.toolbar.emit( 'updateState' );
};
ext.menueditor.ui.widget.SaveTool.prototype.onUpdateState = function () {};
