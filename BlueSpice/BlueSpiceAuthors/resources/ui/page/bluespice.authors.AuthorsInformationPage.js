(function( mw, $, bs, undefined ) {
	bs.util.registerNamespace( 'bs.authors.info' );

	bs.authors.info.AuthorsInformationPage = function AuthorsInformationPage( name, config ) {
		this.authorGrid = null;
		bs.authors.info.AuthorsInformationPage.super.call( this, name, config );
	};

	OO.inheritClass( bs.authors.info.AuthorsInformationPage, StandardDialogs.ui.BasePage );

	bs.authors.info.AuthorsInformationPage.prototype.setupOutlineItem = function () {
		bs.authors.info.AuthorsInformationPage.super.prototype.setupOutlineItem.apply( this, arguments );

		if ( this.outlineItem ) {
			this.outlineItem.setLabel( mw.message( 'bs-authors-info-dialog' ).plain() );
		}
	};

	bs.authors.info.AuthorsInformationPage.prototype.setup = function () {
		return;
	};

	bs.authors.info.AuthorsInformationPage.prototype.onInfoPanelSelect = function () {
		var me = this;
		if ( me.authorGrid === null ){
			mw.loader.using( 'ext.bluespice.extjs').done( function () {
				Ext.onReady( function( ) {
					me.authorGrid = Ext.create( 'BS.Authors.grid.Authors', {
						title: false,
						renderTo: me.$element[0],
						width: me.$element.width(),
						height: me.$element.height()
					});
				}, me );
			});
		}
	}

	bs.authors.info.AuthorsInformationPage.prototype.getData = function () {

		var dfd = new $.Deferred();
		mw.loader.using( 'ext.bluespice.extjs').done( function () {
			Ext.require( 'BS.Authors.grid.Authors', function() {
				dfd.resolve();
			});
		});
		return dfd.promise();
	};

	registryPageInformation.register( 'authors_infos', bs.authors.info.AuthorsInformationPage );

})( mediaWiki, jQuery, blueSpice );
