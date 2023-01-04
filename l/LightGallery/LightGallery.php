<?php
/**
 * LightGallery MediaWiki Extension
 * @version 0.0.2
 * @example at http://ironharvest.wiki/
 * @author [http://www.gilluminate.com Nikolay Filippov]
 * @license [http://www.gnu.org/licenses/gpl.html GPLv3]
*/

/**
 * LightGallery - jQuery Plugin
 * @version: 2.0 (7 Apr 2018)
 * @example at https://sachinchoolur.github.io/lightGallery/
 * @license www.sachinchoolur.github.io/lightGallery/docs/license.html
 * @copyright Copyright 2018 Sachin Choolur
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	echo( "This file is part of an extension to the MediaWiki software and cannot be used standalone.\n" );
	die( 1 );
}

$lgLightGalleryOptions = "{}";
 
//Register Credits
$wgExtensionCredits['media'][] = array(
    'name'        => 'LightGallery',
    'url'         => 'http://www.mediawiki.org/wiki/Extension:LightGallery',
    'author'      => '[https://github.com/Soljdev Nikola Filippov]',
    'description' => 'A simple and fancy FancyBox alternative',
    'version'     => '0.0.2'
);

$wgResourceModules['ext.LightGallery'] = array(
	'scripts' => array('lightGallery/js/lightgallery.js','ext.LightGallery.js'),
	'styles' => array('lightGallery/css/lightgallery.min.css', "lightGallery/css/lg-transitions.min.css"),
	'localBasePath' => dirname( __FILE__ ).'/modules',
	'remoteExtPath' => 'LightGallery/modules',
);

$wgHooks['BeforePageDisplay'][] = 'lgBeforePageDisplay';

function lgBeforePageDisplay(&$out){
	global $lgLightGalleryOptions;
	$out->addModules( 'ext.LightGallery' );
	$out->addInlineScript('var lgLightGalleryOptions = '.$lgLightGalleryOptions.';');
	return true;
}