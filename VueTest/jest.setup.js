/* eslint-disable no-undef */
// Assign things to "global" here if you want them to be globally available during tests
global.mockLocalStorage = {};

// Mock MW object
global.mw = {
	config: {
		get: jest.fn()
	},
	user: {
		isAnon: jest.fn().mockReturnValue( true )
	},
	language: {
		getFallbackLanguageChain: function () {
			return [ 'en' ];
		}
	},
	storage: {
		get: jest.fn( function () {
			return function ( key ) {
				return mockLocalStorage[ key ];
			};
		} ),
		set: jest.fn( function () {
			return function ( key, value ) {
				mockLocalStorage[ key ] = value;
			};
		} )
	}
// other mw properties as needed...
};
