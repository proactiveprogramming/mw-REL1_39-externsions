<?php
//Website http://www.mediawiki.org/wiki/Extension:rsspublisher
//cc-by-sa 4.0 by Thiemo Schuff

if (!defined('MEDIAWIKI')) {
	echo <<<EOT
To install my extension, put the following line in LocalSettings.php:
require_once( "$IP/extensions/RSSpublisher/RSSpublisher.php" );
EOT;
	exit( 1 );
}

$wgRSSpublisher['version'] = '1.4 build 20150818';

$wgExtensionCredits['specialpage'][] = array(
	'path'           => __FILE__,
	'name'           => 'RSSpublisher',
	'descriptionmsg' => 'rsspublisher-desc',
	'author'         => '[http://www.mediawiki.org/wiki/User:Starwhooper Thiemo Schuff]',
	'url'            => 'https://www.mediawiki.org/wiki/Extension:RSSpublisher',
	'version'        => $wgRSSpublisher['version'],
	'license-name' => 'cc-by-sa 4.0'
);

$dir = dirname(__FILE__) . '/';

$wgAutoloadClasses['SpecialRSSpublisher'] = $dir . 'SpecialRSSpublisher.php';
$wgExtensionMessagesFiles['RSSpublisher'] = $dir . 'RSSpublisher.i18n.php';
$wgSpecialPages['RSSpublisher'] = 'SpecialRSSpublisher';
$wgSpecialPageGroups['RSSpublisher'] = 'other';



$wgHooks['SkinBuildSidebar'][] = 'AddRSStoSidebar';

$wgFooterIcons['valitatedby']['w3c'] = array("src" => $wgServer."/extensions/RSSpublisher/valid-rss-rogers.png", "url" => "http://validator.w3.org/feed/check.cgi?url=".$wgServer."/t%253DSpecial%253ARSSpublisher", "alt" => "[Valid RSS]");
 
function AddRSStoSidebar( $skin, &$bar ) {
	global $wgRSSpublisher;
	global $wgServer;
	if (!isset($wgRSSpublisher['showatsidebarnavigation']) or $wgRSSpublisher['showatsidebarnavigation'] == true) $bar['Navigation'][] = array('text' => "RSS", 'href' => "t=Special:RSSpublisher");	
	if (!isset($wgRSSpublisher['showatsidebarrss']) or $wgRSSpublisher['showatsidebarrss'] == true) $bar['RSS'] = '<a href="'.$wgServer.'/t=Special:RSSpublisher"><div style="font-weight: bold;font-size:0.75em;font-family:sans-serif; color: #fff; background-color: #f60; padding: 0.2em 0.35em; float: left; text-decoration: none; border: solid 2px; border-color: #f80 #960 #960 #f80;">RSS</div></a>';
    return true;
}