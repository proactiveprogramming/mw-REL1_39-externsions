const VueTestUtils = require( '@vue/test-utils' );
const App = require( './App.vue' );

VueTestUtils.config.global.mocks = {
	$i18n: ( str ) => str
};

VueTestUtils.config.global.directives = {
	'i18n-html': ( el, binding ) => {
		el.innerHTML = `${binding.arg} (${binding.value})`;
	}
};

describe( 'App', () => {
	const wrapper = VueTestUtils.shallowMount( App );

	it( 'contains an H2 element', () => {
		expect( wrapper.find( 'h2' ).exists() ).toBe( true );
	} );
} );
