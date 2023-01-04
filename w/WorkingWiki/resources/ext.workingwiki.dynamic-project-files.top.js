(function ( $, mw ) {

mw.libs.ext.ww.projectFileQueue = [];

window.qpf = function ( selector ) {
	var $sel = $(selector);
	if ( ! $sel.hasClass( 'ww-dynamic-project-file-loading' ) ) {
		$sel.addClass( 'ww-dynamic-project-file-loading' );
		mw.libs.ext.ww.projectFileQueue.push( $sel );
	}
	// using() runs synchronously - this is a synchronous load.
	// when it loads it will call restartLoadingIfNeeded().
	mw.loader.load( 'ext.workingwiki.dynamic-project-files', undefined, true );
};

if ( mw.hook ) {
	// if we have hooks, i.e. we're in MW 1.22+, we can fire this hook
	// to start loading project files before the page finishes loading.
	mw.hook( 'ww-qpf' ).fire();
} else {
	// else start loading the dpf code, and when it loads it'll start
	// processing files.
	// this is not as good as the hook() solution because it loads
	// on pages that don't need it.
	mw.loader.load( 'ext.workingwiki.dynamic-project-files', undefined, true );
}

})( $, mw )
