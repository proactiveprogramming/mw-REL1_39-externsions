<?php
/**
 * FormInputMik extension
 *
 * @file
 * @ingroup Extensions
 *
 * This file contains the main include file for the FormInputMik extension of
 * MediaWiki.
 *
 * Usage: Add the following line in LocalSettings.php:
 * require_once( "$IP/extensions/FormInputMik/FormInputMik.php" );
 *
 * @author Michele Fella <michele.fella@gmail.com>
 *  Originally based on InputBox by Erik Moeller <moeller@scireview.de>
 *
 * @copyright Public domain
 * @license Public domain
 * @version 0.1.0
 */

// Check environment
if ( !defined( 'MEDIAWIKI' ) ) {
	echo( "This is an extension to the MediaWiki package and cannot be run standalone.\n" );
	die( -1 );
}

/* Configuration */

// Credits
$wgExtensionCredits['parserhook'][] = array(
	'path'           => __FILE__,
	'name'           => 'FormInputMik',
	'author'         => array( 'Michele Fella' ),
	'version'        => '0.1.0',
	'url'            => 'https://www.mediawiki.org/wiki/Extension:FormInputMik',
	'description'    => 'Checks if the page typed in the input box exists and redirects to either the specified formlink (new page) or forminput (edit page).',
	'descriptionmsg' => 'forminputmik-desc',
);

// Shortcut to this extension directory
$dir = dirname( __FILE__ ) . '/';

// Internationalization
$wgExtensionMessagesFiles['FormInputMik'] = $dir . 'FormInputMik.i18n.php';

// Register auto load for the special page class
$wgAutoloadClasses['FormInputMikHooks'] = $dir . 'FormInputMik.hooks.php';
$wgAutoloadClasses['FormInputMik'] = $dir . 'FormInputMik.classes.php';

// Register parser hook
$wgHooks['ParserFirstCallInit'][] = 'FormInputMikHooks::register';
$wgHooks['MediaWikiPerformAction'][] = 'FormInputMikHooks::onMediaWikiPerformAction';
