/**
 * Init Revealjs lib
 */
( function ( Reveal, RevealZoom, RevealNotes, RevealSearch, jQuery ) {
	if ( !document.querySelector( '.reveal' ) ) {
		// do nothing except on action=slide where page contains the .reveal <div>
		return;
	}
	if ( typeof ( Reveal ) === 'undefined' ) {
		// unhandled loading problem
		return;
	}
	Reveal.initialize( {
		history: true,
		center: false,
		controls: true,
		embedded: false,
		slideNumber: true,
		plugins: [ RevealZoom, RevealNotes, RevealSearch ]
	} );

	Reveal.on( 'ready', function () {
		if ( /print-pdf/.test( location.search ) ) {
			// open print if slides are openned in print mode
			window.print();
		}
		// rewrite toc links to go to appropriate slide

		/* eslint-disable-next-line  no-jquery/no-global-selector */
		jQuery( 'li.toclevel-1 li.toclevel-2' ).remove();

		/* eslint-disable-next-line  no-jquery/no-global-selector */
		jQuery( 'li.toclevel-1 > a' ).each( function ( i, toc ) {
			var num = i + 2;
			$( toc ).attr( 'href', '#/' + num );
		} );
	} );

/* eslint-disable-next-line no-undef */
}( Reveal, RevealZoom, RevealNotes, RevealSearch, jQuery ) );
