( function () {
	// Here we override "TranslateInTarget" configuration parameter for the SiteMapper class,
	// to always be false. This is required since we don't know beforehand the target language
	// for this CX entrypoint, and thus we want to avoid redirection to target wiki, where the
	// publishing happens in production wikis. Ultimately, we want to redirect the user to CX
	// within the current wiki with source page, source language and target language prefilled.
	// The target language is the first among the suggested target languages for the current
	// user. In case no such suggested target languages exist, the target language is null
	// but the redirection to CX still happens with the target language missing.
	var siteMapper = new mw.cx.SiteMapper( { TranslateInTarget: false } );
	var sourceLanguage = siteMapper.getCurrentWikiLanguageCode();

	/**
	 * Checks if there is a page in the target language.
	 *
	 * @param {string} code
	 * @return {boolean}
	 */
	function pageInLanguageExists( code ) {
		var domainCode = siteMapper.getWikiDomainCode( code );

		return $( 'li.interlanguage-link.interwiki-' + domainCode ).length === 1;
	}

	/**
	 * Copied from ext.cx.interlanguagelink.init.js
	 *
	 * This method creates a list of target languages that could be suggested to the current user:
	 * - The MediaWiki user interface language.
	 * - Accept-Language.
	 * - Browser interface language.
	 * It filters out page language and languages in which the article DOES exist, and returns
	 * the first language in the array if the array is not empty or null elsewise.
	 *
	 * @return {string|null} Target language
	 */
	function getSuggestedTargetLanguage() {
		var pageLanguage = mw.config.get( 'wgPageContentLanguage' ).split( '-' )[ 0 ];
		var possibleTargetLanguages = [];
		possibleTargetLanguages.push( mw.config.get( 'wgUserLanguage' ) );
		possibleTargetLanguages.push( mw.uls.getBrowserLanguage() );

		Array.prototype.push.apply( possibleTargetLanguages, mw.uls.getAcceptLanguageList() );
		Array.prototype.push.apply( possibleTargetLanguages, mw.uls.getPreviousLanguages() );

		// Language codes can have country extensions like en-US.
		// Remove them so that it is like domain code format.
		possibleTargetLanguages = possibleTargetLanguages.map( function ( language ) {
			return language.split( '-' )[ 0 ];
		} );

		// Replace possibly non-standard, macro and duplicate language codes
		// with normalized counterparts
		var splitCodes = {
			// Suggest both varieties of Belarusian when requesting 'be'
			be: [ 'be', 'be-tarask' ],
			// Suggest both varieties of Norwegian when requesting 'no'
			no: [ 'nb', 'nn' ]
		};

		for ( var splitCode in splitCodes ) {
			var specialCodeIndex = possibleTargetLanguages.indexOf( splitCode );
			if ( specialCodeIndex > -1 ) {
				possibleTargetLanguages.splice( specialCodeIndex, 1 );
				Array.prototype.push.apply( possibleTargetLanguages, splitCodes[ splitCode ] );
			}
		}

		possibleTargetLanguages = possibleTargetLanguages.filter( function ( language ) {
			// Code should not be a language in which page exists.
			// Also it should be a known language for ULS
			return language !== pageLanguage &&
					!pageInLanguageExists( language ) &&
					language !== $.uls.data.getAutonym( language );
		} );

		return possibleTargetLanguages.length ? possibleTargetLanguages[ 0 ] : null;
	}

	var cxEntrypointUrl = siteMapper.getCXUrl(
		mw.config.get( 'wgPageName' ),
		null,
		sourceLanguage,
		getSuggestedTargetLanguage()
	);

	var translateActionItem = {
		name: 'cxTranslate',
		icon: 'add',
		text: mw.msg( 'cx-uls-translate-page-quick-action-label' ),
		href: cxEntrypointUrl
	};

	mw.uls.ActionsMenuItemsRegistry.register( translateActionItem );
}() );
