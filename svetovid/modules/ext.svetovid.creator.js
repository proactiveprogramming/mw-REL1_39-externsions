( function () {
	$( function () {
		const CASES = [
			'nom',
			'gen',
			'dat',
			'acc',
			'inst',
			'loc',
			'voc'
		];

		const NUMBERS = [
			'sg',
			'pl'
		];

		var api = new mw.Api();

		var searchForInput = OO.ui.infuse( $( '#sv-search-for-input' ) );
		var searchForChange = OO.ui.infuse( $( '#sv-search-for-change' ) );
		var cbSg = OO.ui.infuse( $( '#sv-gr-sg' ) );
		var cbPl = OO.ui.infuse( $( '#sv-gr-pl' ) );
		var namespaceSelect = OO.ui.infuse( $( '#sv-ns-select' ) );
		var searchButton = OO.ui.infuse( $( '#sv-search-button' ) );
		var linkStatsLabel = OO.ui.infuse( $( '#sv-page-link-stats' ) );
		setupInitial();

		namespaceSelect.setDisabled( false );

		var targetPageId = 0;

		GrammarDialogInit();
		ResultsDialogInit();
		var gDialog = new GrammarDialog();
		var rDialog = new ResultsDialog();
		var windowManager = new OO.ui.WindowManager();
		$( document.body ).append( windowManager.$element );
		windowManager.addWindows( [ gDialog, rDialog ] );

		function updateLinkStats( stats ) {
			linkStatsLabel.setLabel( mw.message( 'svetovid-links-to-from', stats.in, stats.out ).text() );
		}

		function setupInitial() {
			var pageOkButton = OO.ui.infuse( $( '#sv-page-input-ok' ) );
			var pageInput = OO.ui.infuse( $( '#sv-page-input' ) );
			OO.ui.infuse( $( '#sv-grammar-set' ) ).toggle( false );
			OO.ui.infuse( $( '#sv-options-set' ) ).toggle( false );
			searchButton.toggle( false );

			function nextCallback() {
				var title = pageInput.getMWTitle();

				if ( !title ) {
					mw.notify( mw.message( 'svetovid-invalid-title' ).text(),
						{
							autoHide: true,
							type: 'error'
						} );
					return;
				}

				api.get( {
					action: 'query',
					prop: [ 'info', 'ab_linkstats' ],
					titles: title.getPrefixedDb(),
					ablsdirectonly: true,
					ablscontentonly: true,
					ablsthroughredirects: true
				} ).done( function ( response ) {
					if ( Object.keys( response.query.pages )[0] === '-1' ) {
						mw.notify( mw.message( 'svetovid-no-such-page', title.getPrefixedText() ).text(),
							{
								autoHide: true,
								type: 'error'
							} );
					} else {
						targetPageId = Object.keys( response.query.pages )[0];
						onPageSelected( title.getNameText(), title.getNamespaceId() );
						updateLinkStats( Object.values( response.query.pages )[0].ab_linkstats );
					}
				} );
			}
			var nextCallbackD = mw.util.debounce( 100, nextCallback );
			var requestGrammarD = mw.util.debounce( 100, requestGrammar );

			pageOkButton.on( 'click', nextCallbackD );
			pageInput.on( 'enter', nextCallbackD );
			searchForInput.on( 'enter', requestGrammarD );
			searchForChange.on( 'click', requestGrammarD );
			cbSg.on( 'change', onCheckboxChange );
			cbPl.on( 'change', onCheckboxChange );
			searchButton.on( 'click', mw.util.debounce( 100, doSearch ) );

			if ( pageInput.getValue().length > 0 ) {
				// preloaded value
				nextCallback();
			}
		}

		function getIsSelectedForNumber( number ) {
			if ( number === "sg" ) {
				return cbSg.isSelected();
			} else {
				return cbPl.isSelected();
			}
		}

		function onCheckboxChange() {
			for ( i in NUMBERS ) {
				var number = NUMBERS[i];
				var checked = getIsSelectedForNumber( number );

				for ( j in CASES ) {
					var grCase = CASES[j];
					var input = OO.ui.infuse( $( '#sv-gr-inp-' + number + '-' + grCase ) );
					input.setDisabled( input.getValue() === '' || !checked );
				}
			}

			onActiveFieldsChanged();
		}

		function onPageSelected( pageTitle, ns ) {
			var defNamespaces = mw.config.get( 'wgSvetovidDefaultNamespaces' );
			if ( defNamespaces[ns] ) {
				namespaceSelect.setValue( defNamespaces[ns] );
			}

			searchForInput.setValue( pageTitle );

			OO.ui.infuse( $( '#sv-grammar-set' ) ).toggle( true );
			requestGrammar();
		}

		function requestGrammar() {
			api.get( {
				action: 'polishdecl',
				text: searchForInput.getValue()
			} ).done( function ( response ) {
				var instance = windowManager.openWindow( gDialog, response.data );
				instance.closed.then( function ( data ) {
					onLemmasSelected( data );
				} )
			} ).fail( function ( response ) {
				console.error( response );
				mw.notify( mw.message( 'svetovid-declension-unavailable' ).text(),
					{
						autoHide: true,
						type: 'error'
					} );
			} );
		}

		function onLemmasSelected( data ) {
			$( '#sv-grammar-table' ).css('visibility', 'visible');
			OO.ui.infuse( $( '#sv-options-set' ) ).toggle( true );
			searchForInput.focus();
			searchButton.toggle( true );

			for ( i in NUMBERS ) {
				var number = NUMBERS[i];
				var checked = getIsSelectedForNumber( number );
				var impossibleCount = 0;

				for ( j in CASES ) {
					var grCase = CASES[j];
					var text = '';
					var impossible = false;

					for ( k in data ) {
						if ( !data[k].forms[number] || !data[k].forms[number][grCase] ) {
							impossible = true;
							break;
						}

						if ( k > 0 ) text += ' ';
						if ( data[k].declensible !== "true" ) {
							text += data[k].word;
						} else {
							text += data[k].forms[number][grCase][0];
						}
					}

					var input = OO.ui.infuse( $( '#sv-gr-inp-' + number + '-' + grCase ) );
					if ( impossible ) {
						// „Niemożliwe nam się dostało,
						//  nie ma powodów do łez” ~ Kwiat Jabłoni
						input.setValue( '' );
						input.setDisabled( true );
						impossibleCount++;
					} else {
						input.setValue( text );
						input.setDisabled( !checked );
					}
				}

				if ( number === 'sg' ) {
					cbSg.setDisabled( impossibleCount === CASES.length );
				} else {
					cbPl.setDisabled( impossibleCount === CASES.length );
				}
			}

			onActiveFieldsChanged();
		}

		function onActiveFieldsChanged() {
			var activeCount = 0;

			for ( i in NUMBERS ) {
				var number = NUMBERS[i];

				for ( j in CASES ) {
					var grCase = CASES[j];
					if ( !OO.ui.infuse( $( '#sv-gr-inp-' + number + '-' + grCase ) ).isDisabled() ) {
						activeCount++;
						break;
					}
				}
			}

			searchButton.setDisabled( activeCount === 0 );
		}

		function doSearch() {
			var texts = [];
			for ( i in NUMBERS ) {
				var number = NUMBERS[i];

				for ( j in CASES ) {
					var grCase = CASES[j];
					var input = OO.ui.infuse( $( '#sv-gr-inp-' + number + '-' + grCase ) );
					if ( !input.isDisabled() ) {
						var val = input.getValue();
						if ( !( val in texts ) ) {
							texts.push( val );
						}
					}
				}
			}

			api.post( {
				action: 'svsearch',
				pageid: targetPageId,
				texts: texts,
				namespaces: namespaceSelect.getValue(),
				ignorelinking: OO.ui.infuse( $( '#sv-omit-linkshere' ) ).isSelected()
			} ).done( function ( response ) {
				if ( Object.keys( response ).length === 0 ) {
					OO.ui.alert( mw.message( 'svetovid-svsearch-no-results' ).text() );
				} else {
					var instance = windowManager.openWindow( rDialog, response );
				}
			} ).fail( function ( response ) {
				console.error( response );
				mw.notify( mw.message( 'svetovid-search-error' ).text(),
					{
						autoHide: true,
						type: 'error'
					} );
			} );
		}
	} );
}() );
