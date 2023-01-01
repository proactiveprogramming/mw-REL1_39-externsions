function ResultsDialog( config ) {
	ResultsDialog.super.call( this, config );
}

function ResultsDialogInit() {
	OO.inheritClass( ResultsDialog, OO.ui.ProcessDialog );

	ResultsDialog.static.name = 'resultsDialog';
	ResultsDialog.static.size = 'large';
	ResultsDialog.static.title = mw.message( 'svetovid-results-title' ).text();

	ResultsDialog.static.actions = [
		{
			action: 'continue',
			flags: [ 'primary', 'progressive' ],
			label: 'OK'
		}
	];

	ResultsDialog.prototype.initialize = function () {
		ResultsDialog.super.prototype.initialize.call( this );

		this.content = new OO.ui.PanelLayout( {
			padded: true,
			expanded: false
		} );
		this.$body.append( this.content.$element );
	};

	ResultsDialog.prototype.getActionProcess = function ( action ) {
		this.close();
		return ResultsDialog.super.prototype.getActionProcess.call( this, action );
	};

	ResultsDialog.prototype.getSetupProcess = function ( data ) {
		data = data || {};
		this.rawData = data;

		return ResultsDialog.super.prototype.getSetupProcess.call( this, data )
			.next( function () {
				this.content.$element.empty();
				this.checkboxes = [];
				this.dropdowns = [];

				for ( i in data ) {
					var record = data[i];
					var fieldset = new OO.ui.FieldsetLayout( {
						label: record.title
					} );

					var linkButton = new OO.ui.ButtonWidget( {
						label: mw.message( 'svetovid-edit-button-label' ).text(),
						icon: 'edit',
						flags: [
							'primary',
							'progressive'
						],
						href: record.link,
						target: '_blank',
						title: mw.message( 'svetovid-edit-in-new-tab' ).text()
					} );

					fieldset.addItems( [
						new OO.ui.FieldLayout(
							new OO.ui.Widget( {
								content: [
									new OO.ui.HorizontalLayout( {
										items: [
											linkButton,
											new OO.ui.LabelWidget( {
												label: mw.message( 'svetovid-links-to-from',
													record.inlinks, record.outlinks ).text()
											} )
										]
									} )
								]
							} ),
							{
								align: 'top'
							}
						)
					] );

					this.content.$element.append( fieldset.$element );
				}
			}, this );
	};
}
