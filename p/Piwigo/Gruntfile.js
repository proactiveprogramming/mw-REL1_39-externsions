/* eslint-env node, es6 */
module.exports = function ( grunt ) {
	var conf = grunt.file.readJSON( 'extension.json' );

	grunt.loadNpmTasks( 'grunt-banana-checker' );
	grunt.loadNpmTasks( 'grunt-eslint' );
	grunt.loadNpmTasks( 'grunt-stylelint' );

	grunt.initConfig( {
		eslint: {
			options: {
				cache: true,
				fix: grunt.option( 'fix' )
			},
			all: '.'
		},
		stylelint: {
			all: [
				'**/*.{css,less}',
				'!node_modules/**',
				'!vendor/**'
			]
		},
		banana: conf.MessagesDirs,

		copy: {
			dist: {
			  expand: true,
			  cwd: 'node_modules/baguettebox.js/dist',
			  src: ['baguetteBox.min.css', 'baguetteBox.min.js'],
			  dest: 'resources/ext.baguetteBox'
			}
		  }
	} );

	grunt.loadNpmTasks("grunt-contrib-copy");

	grunt.registerTask( 'test', [ 'eslint', 'stylelint', 'banana' ] );
	grunt.registerTask( 'default', 'test' );
	grunt.registerTask( 'installBaguette', ['copy'] );
};

