( function( mw, $, bs ) {
	bs.util.registerNamespace( 'bs.bookshelf.dialog' );

	bs.bookshelf.dialog.AddToBook = function( config ) {
		config.size = 'medium';
		bs.bookshelf.dialog.AddToBook.parent.call( this, config );

		this.pageName = config.pagename.replace( '_', ' ' );
	};

	OO.inheritClass( bs.bookshelf.dialog.AddToBook, OOJSPlus.ui.dialog.FormDialog );

	bs.bookshelf.dialog.AddToBook.prototype.getActions = function() {
		return [ 'cancel', 'add' ];
	};

	bs.bookshelf.dialog.AddToBook.prototype.getTitle = function() {
		return mw.message( 'bs-bookshelf-add-to-book-label' ).plain();
	};

	bs.bookshelf.dialog.AddToBook.prototype.initialize = function() {
		bs.bookshelf.dialog.AddToBook.parent.prototype.initialize.call( this );
		this.actions.setAbilities( { add: false } );
	};

	bs.bookshelf.dialog.AddToBook.prototype.getFormItems = function() {
		this.bookPicker = new OOJSPlus.ui.widget.StoreDataInputWidget( {
			id: 'book-picker',
			queryAction: 'bs-bookshelf-store',
			labelField: 'book_displaytext',
			groupBy: 'book_type',
			groupLabelCallback: function( label, data ) {
				var location = bs.bookshelf.storageLocationRegistry.lookup( label );
				return location.getLabel();
			}
		} );
		this.bookPicker.connect( this, {
			change: function() {
				var data = this.bookPicker.getSelectedItemData();
				if ( typeof data === 'object' && data !== null ) {
					var type = data.book_type;
					var location = bs.bookshelf.storageLocationRegistry.lookup( type );
					if ( location && location.allowChangingPageTags() ) {
						this.overrideBookshelfTagLayout.$element.show();
					} else {
						this.overrideBookshelfTag.setSelected( false );
						this.overrideBookshelfTagLayout.$element.hide();
					}
					this.updateSize();
					this.actions.setAbilities( { add: true } );
				} else {
					this.actions.setAbilities( { add: false } );
				}
			}.bind( this )
		} );
		this.alias = new OO.ui.TextInputWidget( {
			value: this.pageName,
			id: 'page-alias'
		} );
		this.overrideBookshelfTag = new OO.ui.CheckboxInputWidget( {
			id: 'override-bookshelf-tag'
		} );
		this.overrideBookshelfTagLayout = new OO.ui.FieldLayout( this.overrideBookshelfTag, {
			label: mw.message( 'bs-bookshelf-add-to-book-label-mod-bstag' ).plain(),
			align: 'inline'
		} );
		this.overrideBookshelfTagLayout.$element.hide();

		return [
			new OO.ui.FieldLayout( this.bookPicker, {
				label: mw.message( 'bs-bookshelf-add-to-book-label-book' ).plain(),
				align: 'top'
			} ),
			new OO.ui.FieldLayout( this.alias, {
				label: mw.message( 'bs-bookshelf-add-to-book-label-alias' ).plain(),
				align: 'top'
			} ),
			this.overrideBookshelfTagLayout
		];
	};

	/**
	 *
	 * @param string action
	 * @returns OO.ui.Process|null if not handling
	 */
	OOJSPlus.ui.dialog.FormDialog.prototype.onAction = function( action ) {
		if ( action === 'add' ) {
			var dfd = $.Deferred();
			var selectedBook = this.bookPicker.getSelectedItemData();
			if ( selectedBook === null ) {
				// Sanity
				dfd.reject( new OO.ui.Error() );
			}
			var alias = this.alias.getValue(),
				modifyBookshelfTag = this.overrideBookshelfTag.isSelected(),
				messageTitle = mw.message( 'bs-bookshelf-add-to-book-label' ).plain(),
				storage = bs.bookshelf.storageLocationRegistry.lookup( selectedBook.book_type );

			var wikiText = "\n* [[{0}|{1}]]".format(
				this.pageName,
				alias
			);

			storage.appendText( null, wikiText, modifyBookshelfTag, selectedBook.page_id ).done( function() {
				mw.notify(
					mw.message( 'bs-bookshelf-add-to-book-added', selectedBook.book_displaytext ).plain(),
					{ title: messageTitle }
				);
				if ( modifyBookshelfTag ) {
					mw.notify(
						mw.message( 'bs-bookshelf-add-to-book-mod-bstag' ).plain(),
						{ title: messageTitle }
					);
					this.close( { needsReload: true } );
				}
				this.close();
				dfd.resolve();
			}.bind( this ) ).fail( function() {
				dfd.reject( new OO.ui.Error() );
			} );

			return dfd.promise();
		}

		return OOJSPlus.ui.dialog.FormDialog.parent.prototype.onAction.call( this, action );
	};

} ) ( mediaWiki, jQuery, blueSpice );
