(function( mw, $, bs ) {
	bs.util.registerNamespace( 'bs.expiry.info' );

	bs.expiry.info.ExpiryInformationPage = function ExpiryInformationPage( name, config ) {
		this.expiryGrid = null;
		bs.expiry.info.ExpiryInformationPage.super.call( this, name, config );
	};

	OO.inheritClass( bs.expiry.info.ExpiryInformationPage, StandardDialogs.ui.BasePage );

	bs.expiry.info.ExpiryInformationPage.prototype.setupOutlineItem = function () {
		bs.expiry.info.ExpiryInformationPage.super.prototype.setupOutlineItem.apply( this, arguments );

		if ( this.outlineItem ) {
			this.outlineItem.setLabel( mw.message( 'bs-expiry-info-dialog' ).plain() );
		}
	};

	bs.expiry.info.ExpiryInformationPage.prototype.setup = function () {
		return;
	};

	bs.expiry.info.ExpiryInformationPage.prototype.onInfoPanelSelect = function () {
		var me = this;
		if ( me.expiryGrid === null ){
			mw.loader.using( 'ext.bluespice.extjs' ).done( function () {
				Ext.onReady( function( ) {
					me.expiryGrid = Ext.create( 'BS.Expiry.flyout.dataview.NextExpiry', {
						title: false,
						renderTo: me.$element[0],
						width: me.$element.width()
					});
				}, me );
				this.specialPageButton = new OO.ui.ButtonWidget( {
					label: mw.message( 'bs-expiry-info-dialog-button-label' ).plain(),
					href: mw.util.getUrl( "Special:Expiry" )
				} );
				me.$element.append( this.specialPageButton.$element );
			});
		}
	}

	bs.expiry.info.ExpiryInformationPage.prototype.getData = function () {
		var dfd = new $.Deferred();
		mw.loader.using( 'ext.bluespice.extjs' ).done( function () {
			Ext.require( 'BS.Expiry.flyout.dataview.NextExpiry', function() {
				dfd.resolve();
			});
		});
		return dfd.promise();
	};

	registryPageInformation.register( 'expiry_infos', bs.expiry.info.ExpiryInformationPage );

})( mediaWiki, jQuery, blueSpice );
