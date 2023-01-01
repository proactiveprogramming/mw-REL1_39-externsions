function GrammarDialog( config ) {
	GrammarDialog.super.call( this, config );
}

function GrammarDialogInit() {
	OO.inheritClass( GrammarDialog, OO.ui.ProcessDialog );

	GrammarDialog.static.name = 'grammarDialog';
	GrammarDialog.static.size = 'large';
	GrammarDialog.static.title = mw.message( 'svetovid-choose-lemmas' ).text();

	GrammarDialog.static.actions = [
		{
			action: 'continue',
			flags: [ 'primary', 'progressive' ],
			label: 'OK'
		}
	];

	GrammarDialog.prototype.initialize = function () {
		GrammarDialog.super.prototype.initialize.call( this );

		this.content = new OO.ui.PanelLayout( {
			padded: true,
			expanded: false
		} );
		this.$body.append( this.content.$element );
	};

	GrammarDialog.prototype.getActionProcess = function ( action ) {
		if ( action === 'continue' || !action ) {
			var dialog = this;
			return new OO.ui.Process( function () {
				var result = [];

				for ( i in dialog.rawData.words ) {
					var word = dialog.rawData.words[i];
					var dropdown = dialog.dropdowns[i];
					var j = dropdown.getMenu().findSelectedItem().getData();

					var res = word.interpretations[j];
					res.word = word.word;
					if ( res.declensible === "true" ) {
						res.declensible = dialog.checkboxes[i].isSelected().toString()
					}
					result.push( res );
				}

				dialog.close( result );
			} );
		}

		return GrammarDialog.super.prototype.getActionProcess.call( this, action );
	};

	GrammarDialog.prototype.toggleDeclenseCheckbox = function () {
		for ( i in this.checkboxes ) {
			if ( this.checkboxes[i] ) {
				this.dropdowns[i].toggle( this.checkboxes[i].isSelected() );
			}
		}
	};

	GrammarDialog.prototype.getSetupProcess = function ( data ) {
		data = data || {};
		this.rawData = data;

		return GrammarDialog.super.prototype.getSetupProcess.call( this, data )
			.next( function () {
				this.content.$element.empty();
				this.checkboxes = [];
				this.dropdowns = [];

				for ( ix in data.words ) {
					var word = data.words[ix];
					var options = [];
					for ( i in word.interpretations ) {
						var label = word.interpretations[i].lemma;
						if ( word.interpretations[i].shortText ) {
							label += ' – ' + word.interpretations[i].shortText
						}

						options.push( new OO.ui.MenuOptionWidget( {
							data: i,
							label: label,
							title: word.interpretations[i].longText
						} ) );
					}

					var fieldset = new OO.ui.FieldsetLayout( {
						label: mw.message( 'svetovid-lemma-selection', word.word ).text(),
					} );

					var dropdown = new OO.ui.DropdownWidget( {
						$overlay: this.$overlay,
						value: 0,
						menu: {
							items: options
						}
					} );
					dropdown.getMenu().selectItemByData( '0' );

					if ( word.interpretations.length < 2 ) {
						dropdown.setDisabled( true );
					}

					this.dropdowns.push( dropdown );
					fieldset.addItems( [
						new OO.ui.FieldLayout(
							dropdown,
							{
								align: 'top'
							}
						)
					] );

					if ( word.interpretations.length > 1 || word.interpretations[0].declensible === "true" ) {
						var checkbox = new OO.ui.CheckboxInputWidget( {
							selected: true
						} );
						this.checkboxes.push( checkbox );

						fieldset.addItems( [
							new OO.ui.FieldLayout(
								checkbox,
								{
									label: mw.message( 'svetovid-declense', word.word ).text(),
									align: 'inline'
								}
							)
						] );
						var dialog = this;
						checkbox.on( 'change', function() { dialog.toggleDeclenseCheckbox() } );
					} else {
						this.checkboxes.push( null );
					}

					this.content.$element.append( fieldset.$element );
				}
			}, this );
	};
}
