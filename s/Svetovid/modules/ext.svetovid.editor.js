( function () {
	$( function () {
		var linksByNumber = [];
		var linksByText = {};

		var titleRemove = mw.msg( 'svetovid-remove-link-tooltip' );
		var titleAdd = mw.msg( 'svetovid-readd-link-tooltip' );

		var textbox = $( '#wpTextbox1' );
		var originalText = textbox.val();
		var summaryBox = $( '#wpSummary' );
		var summaryTop = $( '#sv-summary-top' );
		var targetTitle = mw.config.get( 'wgSvetovidTargetTitle' );

		/* INITIALIZATION: Find all added links and index them */
		var balance = 0;
		var prevElement;
		$( '#wikiDiff .diff-addedline .diffchange' ).each( function( i ) {
			var elem = $( this );
			var text = elem.text();
			if ( text.indexOf( '[[' ) > -1 ) balance--;
			if ( text.indexOf( ']]' ) > -1 ) balance++;
			if ( balance < -1 || balance > 1 )
				console.error( '[Svetovid] Brace balance out of bounds: ' + balance );

			if ( prevElement || balance === 0 ) {
				if ( balance !== 0 ) {
					console.warn( '[Svetovid] Probably multiple links considered a single one.' );
					balance = 0;    // keep the balance of Force, Luke
				} else {
					// add behaviour only if the link is valid
					elem.mouseenter( onLinkHoverInOut );
					elem.mouseleave( onLinkHoverInOut );
					elem.click( onLinkClick );

					if ( prevElement ) {
						prevElement.mouseenter( onLinkHoverInOut );
						prevElement.mouseleave( onLinkHoverInOut );
						prevElement.click( onLinkClick );
					}
				}

				var id = linksByNumber.length;
				elem.attr( 'linknum', id );
				elem.attr( 'title', titleRemove );

				var link = {
					num: id,
					elements: [ elem ],
					text: elem.text(),
				};

				if ( prevElement ) {
					prevElement.attr( 'linknum', id );
					prevElement.attr( 'title', titleRemove );
					link.elements.push( prevElement );
					var contents = prevElement.parent().contents();
					var ix = contents.index( prevElement );

					var textNode = contents[ix + 1];
					if ( textNode && textNode.nodeType === 3 ) {
						link.text = prevElement.text() + contents[ix + 1].textContent + elem.text();
					} else {
						link.text = prevElement.text() + elem.text();
					}

					prevElement = null;
				}

				link.linkc = (link.text.match(/\[\[/g) || []).length;
				link.stripped = stripLink( link.text );
				linksByNumber.push( link );
				linksByText[link.text] = link;
			} else {
				prevElement = $( elem );
			}
		} );

		// Highlight on mouseover
		function onLinkHoverInOut() {
			var id = $( this ).attr( 'linknum' );
			linksByNumber[id].elements.map( function ( x ) {
				x.toggleClass( 'sv-hl' );
			} );
		}

		// clicking time!
		function onLinkClick() {
			var elem = $( this );
			var link = linksByNumber[elem.attr('linknum')];

			if ( elem.hasClass( 'sv-removed' ) ) {
				// adding link
				link.elements.map( function ( x ) {
					x.removeClass( 'sv-removed' );
					x.attr( 'title', titleRemove );
				} );
			} else {
				// removing link
				link.elements.map( function ( x ) {
					x.addClass( 'sv-removed' );
					x.attr( 'title', titleAdd );
				} );
			}

			updateTexts();
		}

		// update the edit field, edit summary and warning above edit field
		function updateTexts() {
			var text = originalText;
			var prevIndex = 0;
			var linkCount = 0;
			linksByNumber.forEach( function ( link, i ) {
				prevIndex = prevIndex + text.substr( prevIndex ).indexOf( link.text );
				if ( prevIndex < 0 ) throw "Can't find '" + link.text + "'";

				if ( link.elements[0].hasClass( 'sv-removed' ) ) {
					text = text.substr( 0, prevIndex ) + text.substr( prevIndex ).replace( link.text, link.stripped );
				} else {
					linkCount += link.linkc;
				}

				prevIndex++;
			} );

			textbox.val( text );
			summaryBox.val( mw.message( 'svetovid-edit-summary', linkCount, targetTitle ).text() );
			summaryTop.text( mw.message( 'svetovid-summary-top', linkCount ).parse() );
		}

		// dissect a link and return a version of it without braces
		function stripLink( text ) {
			var parts = text.match(/^([^\[]*)(\[\[)([^|]*?)(\|?)([^|]*?)(]])([^\]]*)$/);
			return parts[1] + parts[5] + parts[7];      // prefix + text + suffix
		}
	} );
}() );
