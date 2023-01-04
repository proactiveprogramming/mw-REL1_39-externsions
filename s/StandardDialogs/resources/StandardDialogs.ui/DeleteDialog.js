StandardDialogs = StandardDialogs || {};
StandardDialogs.ui = StandardDialogs.ui || {};

StandardDialogs.ui.DeleteDialog = function StandardDialogsUiDeleteDialog( config ) {
	StandardDialogs.ui.DeleteDialog.super.call( this, config );
};
OO.inheritClass( StandardDialogs.ui.DeleteDialog, StandardDialogs.ui.BaseDialog );

StandardDialogs.ui.DeleteDialog.static.name = 'ext-standard-dialogs-delete';

StandardDialogs.ui.DeleteDialog.prototype.makeSetupProcessData = function () {
	data = StandardDialogs.ui.DeleteDialog.super.prototype.makeSetupProcessData.call( this );
	data.title = mw.message( 'standarddialogs-delete-title', this.getDialogTitlePageName() ).plain();
	data.actions[ 0 ].flags[ 1 ] = 'destructive';

	return data;
};

StandardDialogs.ui.DeleteDialog.prototype.getFormItems = function () {
	this.mainInput = this.reasonCombo = new OO.ui.TextInputWidget( {
		id: this.elementId + '-cbx-reason'
	} );
	this.otherReasonText = new OO.ui.TextInputWidget( {
		id: this.elementId + '-tf-reason'
	} );
	this.watchCheckbox = new OO.ui.CheckboxInputWidget( {
		id: this.elementId + '-cb-watch',
		value: 'watch',
		selected: true
	} );

	return [
		new OO.ui.FieldsetLayout( {
			items: [
				new OO.ui.FieldLayout( this.reasonCombo, {
					label: mw.message( 'deletecomment' ).plain(),
					align: 'top'
				} ),
				new OO.ui.FieldLayout( this.otherReasonText, {
					label: mw.message( 'deleteotherreason' ).plain(),
					align: 'top'
				} ),
				new OO.ui.FieldLayout( this.watchCheckbox, {
					label: mw.message( 'watchthis' ).plain(),
					align: 'inline'
				} )
			]
		} )
	];
};

StandardDialogs.ui.DeleteDialog.prototype.makeDoneActionProcess = function () {
	const dialog = this;

	const dfd = new $.Deferred();
	mw.loader.using( 'mediawiki.api' ).done( function () {
		const mwApi = new mw.Api();
		const params = {
			action: 'delete',
			title: dialog.pageName,
			reason: dialog.reasonCombo.getValue() + dialog.otherReasonText.getValue()
		};
		if ( dialog.watchCheckbox.isSelected() ) {
			params.watchlist = 'watch';
		} else {
			params.watchlist = 'unwatch';
		}
		mwApi.postWithToken( 'csrf', params ).done( function ( data ) {
			dfd.resolve.apply( dialog, arguments );
		} ).fail( function () {
			dfd.reject.apply( dialog, [ new OO.ui.Error( arguments[ 0 ], { recoverable: false } ) ] );
		} );
	} ).fail( function () {
		dfd.reject.apply( dialog, arguments );
	} );

	return new OO.ui.Process( dfd.promise(), this );
};
