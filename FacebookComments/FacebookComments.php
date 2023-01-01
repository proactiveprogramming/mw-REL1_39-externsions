<?php
/*
 * MediaWiki extension to add Facebook Comments
 * Installation instructions can be found on
 * http://www.mediawiki.org/wiki/Extension:FacebookComments
 *
 * @ingroup Extensions
 * @author Jmkim dot com
 * @license GNU Public License
 */
 
// Exit if called outside of MediaWiki
if( !defined( 'MEDIAWIKI' ) ) exit;
 
// SETTINGS
$wgFacebookCommentsNumPosts = 5;
$wgFacebookCommentsWidth = 470;
$wgFacebookCommentsColorscheme = 'light';
 
$dir = dirname(__FILE__).'/';
$wgAutoloadClasses['FacebookComments'] = $dir.'FacebookComments.class.php';
$wgHooks['SkinAfterBottomScripts'][] = 'FacebookComments::renderFacebookComments';
$wgExtensionCredits['parserhook'][] = array(
'path'        => __FILE__,
'name'        => 'FacebookComments',
'version'     => '1.1.2',
'author'      => 'Jmkim dot com',
'description' => 'Facebook Comments Extension',
'url'         => 'http://www.mediawiki.org/wiki/Extension:FacebookComments'
);