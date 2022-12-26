/**
 * MIT License
 */

( function () {

	function SaveSpinner() {
		this.$spinner = null;
		this.isForm = false;

		if ( this.isPopupIframe() ) {
			return;
		}

		this.initialize();

	}

	SaveSpinner.prototype.initialize = function () {
		var self = this,
			/* eslint-disable no-jquery/no-global-selector */
			$saveBtn = $( '#wpSave' ),
			$previewBtn = $( '#wpPreview' ),
			$diffBtn = $( '#wpDiff' );
			/* eslint-enable */

		if ( mw.config.get( 'wgAction' ) === 'formedit' ||
			mw.config.get( 'wgCanonicalSpecialPageName' ) === 'FormEdit'
		) {
			this.isForm = true;
		}

		this.buildSpinnerWrapper();

		if ( $saveBtn ) {
			$( document ).on( 'click', '#wpSave', function () {
				self.onClick( event );
			} );
		}

		if ( $previewBtn ) {
			// Hack for PageForms preview button - there is no better way so far without intrusion
			// into PageForms scripts or this requires deeper investigation
			if ( this.isForm ) {
				$( document ).on( 'click', '#wpPreview', function ( event ) {
					self.onClick( event );
					setTimeout( function () {
						self.hideSpinner();
					}, 3000 );
				} );
			} else {
				$( document ).on( 'click', '#wpPreview', function () {
					self.onClick( event );
				} );
			}
		}

		if ( $diffBtn ) {
			$( document ).on( 'click', '#wpDiff', function () {
				self.onClick( event );
			} );
		}

		// Fixes FF problem with navigating back after form submit
		window.onunload = function () {};

	};

	SaveSpinner.prototype.buildSpinnerWrapper = function () {
		var $innerHtml;

		this.$spinner = $( '<div>' ).addClass( 'savespinner-wrapper' );
		$innerHtml = $( '<div>' ).addClass( 'lds-ring' )
			// Inner divs needed for the ring to rotate 360 degrees
			.append( '<div>' ).append( '<div>' )
			.append( '<div>' ).append( '<div>' )
			.append( '<div>' ).append( '<div>' )
			.append( '<div>' ).append( '<div>' );
		this.$spinner.append( $innerHtml );
		/* eslint-disable no-jquery/no-global-selector */
		$( 'body' ).append( this.$spinner );
		/* eslint-enable */
	};

	SaveSpinner.prototype.onClick = function () {
		this.displaySpinner();
	};

	SaveSpinner.prototype.displaySpinner = function () {
		this.$spinner.addClass( 'savespinner-wrapper--visible' );
	};

	SaveSpinner.prototype.hideSpinner = function () {
		this.$spinner.removeClass( 'savespinner-wrapper--visible' );
	};

	/**
	 * @return {boolean}
	 */
	SaveSpinner.prototype.isPopupIframe = function () {
		try {
			return ( ( window.self !== window.top ) && window.self.frameElement.classList.contains( 'popupform-innerdocument' ) );
		} catch ( e ) {
			return true;
		}
	};

	new SaveSpinner();
}() );
