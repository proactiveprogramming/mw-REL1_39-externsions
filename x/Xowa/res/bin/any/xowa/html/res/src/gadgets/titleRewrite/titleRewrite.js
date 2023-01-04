/*
MediaWiki:Gadget-TitleRewrite.js
*/
/**
 * Altera o t�tulo da p�gina
 *
 * A fun��o procura por um banner como:
 ** <div id="RealTitleBanner">Div que est� oculta
 ** <span id="RealTitle">Novo t�tulo</span>
 ** </div>
 * Um elemento com id=DisableRealTitle desabilita a fun��o
 * @source: [[:en:MediaWiki:Common.js]] ([[en:Special:PermaLink/95609539]]) / [[:en:User:Interiot/js/RealTitle.js]]
 * @see: [[:en:MediaWiki talk:Common.js/Archive 2#Initial letter is shown capitalized due to technical restrictions]]
 * @see: [[:en:Wikipedia:Village pump (technical)/Archive AC#Name technical restrictions workaround]]
 * @author: [[:en:User:Interiot]]
 * @author: [[User:!Silent]]
 * 
 * Usada em: {{autora}}
 */
/*jslint white: true */
/*global jQuery*/
( function( $ ) {
'use strict';

function rewritePageTitle() {
	var $realTitle,
		$realTitleBanner = $( '#RealTitleBanner' );

	if ( $realTitleBanner.length !== 0 && !$( '#DisableRealTitle' ).length ) {
		$realTitle = $( '#RealTitle' );

		if ( $realTitle.length ) {
			$( 'h1:first' ).html( $realTitle.html() );
			$realTitleBanner.hide();
		}
	}
}

$( rewritePageTitle );

}( jQuery ) );