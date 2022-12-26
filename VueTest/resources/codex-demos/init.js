( function () {
	const Vue = require( 'vue' );
	const Sandbox = require( './Sandbox.vue' );

	Vue.configureCompat( {
		MODE: 3
	} );

	Vue.createMwApp( Sandbox )
		.mount( '#vue-root' );
}() );
