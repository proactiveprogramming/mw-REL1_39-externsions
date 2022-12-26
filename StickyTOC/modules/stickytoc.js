$( function () {
	// eslint-disable-next-line no-jquery/no-global-selector
	var $toc = $( '#toc' ),
		float = mw.config.get( 'wgStickyTOCFloat' ),
		forceSticky = mw.config.get( 'wgStickyTOCNavbar' ),
		replaceTitle = mw.config.get( 'wgStickyTOCReplaceTitle' ),
		articleTitle = mw.config.get( 'wgTitle' ),
		$wrapper,
		$parent;

	if ( $toc.length ) {

		if ( replaceTitle && articleTitle ) {
			$toc.find( '#mw-toc-heading' ).text( articleTitle );
		}

		$toc.insertBefore( '#content' );
		/* eslint-disable mediawiki/class-doc, no-jquery/no-parse-html-literal */
		$wrapper = $( '<div/>' )
		/* eslint-enable */
			.addClass( 'sticky-top' )
			.addClass( 'stickytoc' )
			.addClass( 'stickytoc-pull-' + float );
		if ( float === 'aside' ) {
			$parent = $toc.parent();
			$parent.addClass( 'stickytoc-parent' );
		}
		if ( forceSticky ) {
			$wrapper.addClass( 'force-sticky' );
		}
		$toc.wrap( $wrapper );
	}
} );
