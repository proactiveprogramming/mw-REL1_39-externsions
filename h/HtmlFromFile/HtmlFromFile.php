<?php
if (!defined('MEDIAWIKI')) {
	die( 'This file is a MediaWiki extension, it is not a valid entry point' );
}

$wgExtensionCredits['parserhook'][] = array(
	'name' => 'htmlfromfile',
	'version' => '1.0',
	'description' => 'Allows for inclusion of raw html from local files on the server (and accidentally also remote files via https)',
	'author' => 'Constantin Berhard',
	'url' => 'https://gitlab.com/Thunis/HtmlFromFile',
);

// Config

// default value; do not change, but overwrite in LocalSettings.php
$wgHtmlFromFileMappings = array();
/* example:
$wgHtmlFromFileMappings = array(
	'memberlist' => '/home/frank/Documents/memberlist.html',
	'calendar' => '/home/bill/scripts/ics2html/generated_calendar.html',
	'weatherwidget' => 'https://yourweatherservice.com/htmlwidget.php?region=Amsterdam',
); */

// End config

$wgHooks['ParserFirstCallInit'][] = 'HtmlFromFile::onParserSetup';

class HtmlFromFile {
	// Register any render callbacks with the parser
	function onParserSetup (Parser $parser) {
		$parser->setHook('htmlfromfile', 'HtmlFromFile::renderTag');
	}

	function error($errstr) {
		return 'ERROR with extension HtmlFromFile: '.$errstr;
	}

	function renderTag ($input, array $args, Parser $parser, PPFrame $frame) {
		global $wgHtmlFromFileMappings;
		$parser->disableCache();
		if ($input === null) {
			return HtmlFromFile::error('Tag mustn\'t be empty.');
		}
		$alias = $input; // the alias of the file name in the mappings array
		if (!array_key_exists($alias,$wgHtmlFromFileMappings)) {
			return HtmlFromFile::error("Include file alias '$alias' is not defined.");
		}
		$fname = $wgHtmlFromFileMappings[$alias];
		$oldlevel = error_reporting(0); // we want no warning if a file does not exist, but instead return our own error message
		$fcontents = file_get_contents($fname);
		error_reporting($oldlevel);
		if ($fcontents === false) {
			return HtmlFromFile::error("Could not read the file associated with the name '$alias'.");
		}
		// all went fine
		return $fcontents;
	}
}

?>
