( function ( mw ) {
	if ( mw.toolbar ) {
		var iconPath = mw.config.get( 'wgExtensionAssetsPath' ) + '/MathLaTeX/images/';
		mw.toolbar.addButton( {
			imageFile: iconPath + 'button_mathlatex_classic.png',
			speedTip: mw.msg( 'mathlatex_tip' ),
			tagOpen: '<mathlatex width= height= dpi= >',
			tagClose: '</mathlatex>',
			sampleText: mw.msg( 'mathlatex_sample' ),
			imageId: 'mw-editbutton-math'
		} );
	}
}( mediaWiki ) );
