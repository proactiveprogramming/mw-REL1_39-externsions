<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgExtensionCredits['parserhook'][] = array(
	'path'           => __FILE__,
	'name'           => 'Random Image within Commons',
	'descriptionmsg' => 'riwc-desc',
	'version'        => '2.3 beta build 20140916',
	'author'         => '[http://www.mediawiki.org/wiki/User:Starwhooper Thiemo Schuff]',
	'url'            => 'http://www.mediawiki.org/wiki/Extension:RandomImagewithinCommons',
	'license-name'  => 'cc-by-sa 4.0',
);

$wgExtensionMessagesFiles['riwc'] = dirname(__FILE__) . '/RandomImagewithinCommons.i18n.php';
$wgExtensionFunctions[] = 'wfRandomImageFunction';
 
function wfRandomImageFunction() {
	global $wgParser;
	$wgParser->setHook('riwc', 'wfRandomImagewithinsCommons');
}
 
function wfRandomImagewithinsCommons($input, array $args, Parser $parser, PPFrame $frame) {
	global $wgriwc;

	$parser->disableCache();
	
	//set defaults
	if (!isset($wgriwc['size'])) $wgriwc['size'] = '200px';
	
	//connect DB
	$dbr = wfGetDB( DB_SLAVE );	
	
	//Get forbitten files from blacklist
	$res = $dbr->select(array('categorylinks','page'),array('page_title'),array('cl_to = "ricw_blacklist"','cl_type = "file"'),__METHOD__,array(),array('page' => array( 'INNER JOIN',array('cl_from = page_id'))));
	foreach($res as $row) $conditions[] = 'il_to != "'.$row->page_title.'"';
	
	//Get Imagename
	$imagename = $dbr->selectField('imagelinks', 'il_to', $conditions, __METHOD__, array('GROUP BY' => 'il_to', 'ORDER BY' => 'RAND()'));

	//Get Articles with the image
	$res = $dbr->select(array('imagelinks','page'),array('page_title'),array('il_to = "'.$imagename.'"','page_namespace = 0'),__METHOD__,array(),array('page' => array( 'INNER JOIN',array('il_from = page_id'))));
	foreach($res as $row) $article['title'] .= '[['.str_replace('_',' ',$row->page_title).']] & ';
	$article['title'] = substr($article['title'],0,-3);		
		
	//Prompt Tag
	$output = $parser->recursiveTagParse( '<div>[[File:'.$imagename.'|'.$wgriwc['size'].']]<br /> '.wfMessage('riwc-fromarticle').': '.$article['title'].'</div>');
		
	return 	$output;
}
