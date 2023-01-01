<?php
if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'MathJax' );
	wfWarn(
		'Deprecated PHP entry point used for MathJax extension. Please use wfLoadExtension ' .
		'instead, see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return true;
} else {
	die( 'This version of the MathJax extension requires MediaWiki 1.25+' );
}
