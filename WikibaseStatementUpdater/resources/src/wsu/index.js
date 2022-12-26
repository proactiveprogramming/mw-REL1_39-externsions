'use strict';

function showBatchStatus() {
	const header = document.querySelector( '.mw-ext-wsu-heading' );
	const status = document.createElement( 'span' );
	status.classList.add( 'mw-ext-wsu-summary' );
	header.appendChild( status );

	const spinner = document.createElement( 'span' );
	spinner.classList.add( 'mw-ext-wsu-summary__spinner' );

	const params = ( new URL( document.location ) ).searchParams;
	const batch = params.get( 'batch' );
	if ( !batch ) {
		return;
	}

	let wasProcessing = false;

	const api = new mw.Api();

	const updateBatchStatus = function () {
		return api.postWithToken( 'csrf', {
			action: 'wikibasestatementupdater',
			batch: batch,
			formatversion: 2
		} ).then( function ( result ) {
			const wsu = result.wikibasestatementupdater;

			// Batch not yet started
			if ( wsu.status === null ) {
				status.remove();
				return false;
			}

			const completionRate = mw.language.convertNumber(
				parseFloat( ( wsu.count - wsu.incomplete ) / wsu.count * 100 ).toFixed()
			);
			const failedCount = wsu.error;

			status.innerHTML = mw.message( 'wsu-js-batchtable-status', completionRate, failedCount ).parse();

			if ( wsu.status === 'started' && wsu.incomplete > 0 ) {
				wasProcessing = true;
				status.appendChild( spinner );
				return true;
			} else if ( wsu.status === 'started' && wsu.incomplete === 0 && wasProcessing ) {
				location.reload();
				return false;
			}

			return false;
		} );
	};

	const updateLoop = function ( func ) {
		func().then( function ( shouldContinue ) {
			if ( shouldContinue ) {
				setTimeout( function () {
					updateLoop( func );
				}, 5000 );
			}
		} );
	};

	updateLoop( updateBatchStatus );
}

if ( document.body.classList.contains( 'mw-special-WikibaseStatementUpdater' ) ) {
	showBatchStatus();
}
