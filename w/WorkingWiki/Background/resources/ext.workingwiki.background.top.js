(function ( $, mw ) {

var bgactions = [
	'ww-create-background-job',
	'ww-destroy-background-job',
	'ww-merge-background-job',
	'ww-kill-background-job'
];
var hookkey;
var hfn = function ( api, opts ) {
	mw.libs.ext.ww.reloadJobsList( true );
};
for (i in bgactions) {
	hookkey = 'ww-api-' + bgactions[i] + '-done';
	if ( 'hook' in mw ) {
		mw.hook( hookkey ).add( hfn );
	} else {
		mw.libs.ext.ww.hooks[ hookkey ] = hfn;
	}
}

} )($, mw)
