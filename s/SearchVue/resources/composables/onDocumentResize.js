const { ref, onMounted, onUnmounted } = require( 'vue' );

module.exports = function onDocumentResize() {

	const mainContainer = document.documentElement || document;
	const width = ref( mainContainer.clientWidth );
	const height = ref( mainContainer.clientHeight );
	let timeout = null;

	const updateValues = function ( w, h ) {
		width.value = w;
		height.value = h;
	};

	const throttle = function ( callback, delay ) {
		let latest = null;

		const invalidatingCallback = function ( ...args ) {
			latest = null;
			callback( ...args );
		};

		return function ( ...args ) {
			if ( timeout === null ) {
				// not currently throttled; execute, and use timeout as lock
				invalidatingCallback( ...args );
				timeout = setTimeout(
					function () {
						timeout = null;
						if ( latest !== null ) {
							invalidatingCallback( ...latest );
						}
					},
					delay
				);
			} else {
				// throttled, but called again - keep track of data; we'll
				// make sure to invoke our callback once more once throttle
				// delay is over to reflect this latest data
				latest = args;
			}
		};
	};

	const getThrottledResize = throttle(
		function () {
			updateValues( mainContainer.clientWidth, mainContainer.clientHeight );
		},
		200
	);

	onMounted( function () {
		window.addEventListener( 'resize', getThrottledResize );
	} );

	onUnmounted( function () {
		window.addEventListener( 'resize', getThrottledResize );
	} );

	return {
		width,
		height
	};
};
