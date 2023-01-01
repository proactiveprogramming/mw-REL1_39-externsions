<?php
/**
 * NFE Extention
 *
 * This file contains the main include file for the NFE extension of
 * MediaWiki.
 *
 * Usage: Add the following line in LocalSettings.php:
 * require_once( "$IP/extensions/nfe/nfe.php" );
 *
 * @author Robert Mrasek
 * @contact Athanasios Mazarakis (mazarakis@fzi.de)
 * @version 0.3.0
 */
 
if( defined( 'MEDIAWIKI' ) ) {
require_once( 'nfelib.php' );

/** Please spezify the url of the wiki */
$wgWikiURL = "http://www.yourdomain.de/wiki/";

/** Specifies the time in seconds that must have passed after a edit until the next edit counts. */
$wgDelayTime = 1;

/** Default Values, edit as you wish */
/** Deactivates the caching feature of mediawiki */
$wgNfeCacheing = false;

/** Enable or Disable every single Feedback mechanism 
 Use true for enableing, false for disabling */
/* Displaying a simple Thank you Message */
$wgNfeFeedbackThankYou = true;
/* Displaying the amount of contributions */
$wgNfeFeedbackAmount = true;
/* Displaying the % of the wiki contributions */
$wgNfeFeedbackProcent = true;
/* Displaying a small poriton of the Highscore */
$wgNfeFeedbackHighscore = true;
/* Random Possible for a user */
$wgNfeFreedbackRandom = false;
/* No Feedback */
$wgNfeFeedbackNone = false;

/** Igonor users without an account */
$wgNfeIgnorIP = false;

/** If set to true, all Feedbacktexts will be in english
 * If set to false the texts will be in german  */
$wgNfeEnglish = false;


/** Once the extenstion has run for the first time, this flag can be safly tu
rned to false to gain a performenc boost */
$wgNfeCreatDatabase = true;


/** Normally no editing after this is needed */
###############################################
$wgNfePath = $wgWikiURL."/extensions/nfe/";
$wgExtensionCredits['parserhook'][] = array(
       'name' => 'nfe',
       'author' =>'Robert Mrasek', 
       'url' => '', 
       'description' => 'This extension gives different feedbacks on edits in the wiki',
       'version' => '0.1.0'
       );

#Register Our Hooks
$wgHooks['OutputPageBeforeHTML'][] = 'NfeParserHook';
# Disable Caching
# When you make changes to this configuration file, this will make
# sure that cached pages are cleared.
if(!$wgNfeCacheing){
	header("Expires: 0");
	$wgCacheEpoch = max( $wgCacheEpoch, gmdate( 'YmdHis', @filemtime( __FILE__ ) ) );
	$wgEnableParserCache = false;
	$wgCachePages = false;
	$wgEnableUploads = true;
}
}else{
	echo( "This file is an extension to the MediaWiki software and cannot be used standalone.\n" );
	die( 1 );
}
?>
