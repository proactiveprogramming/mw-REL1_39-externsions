<?php
/**
 * MediaWiki MathLaTeX extension
 *
 * @brief MathLaTeX expresses LaTeX statements as png images and inserts
 * them into wiki articles.
 *
 * @file
 * @name MathLaTeX
 * @version 1.0
 * file version 1.0
 * @author Jesse B. Dooley
 * @date January 8, 2016
 * @ingroup Extensions
 * @link http://mathlatex.sourceforge.net
 *
 * @section LICENSE
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 3 of
 * the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * General Public License for more details at
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @see README
 * @see VERSION
 * @see COPYING
 *
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( "This is not a valid entry point to MediaWiki./n" );
}

// Extension credits that will show up on Special:Version -specialpage
$wgExtensionCredits['parserhook'][] = array(
	'path' => __FILE__,
	'name' => 'MathLaTeX',
	'version' => '1.0.0',
	'author' => 'Jesse B. Dooley',
	'descriptionmsg' => 'mathlatex-desc',
	'url' => 'http://www.mediawiki.org/wiki/Extension:MathLaTeX',
	'license-name' => 'GPL-3.0+',
);

/**
 * DEFINES
 */
define( "DEFAULT_DPI", 120 );
define( "WHITESPACE_REGEX", " \v\t\n\r\0\x0B" );

/**
 * $MathDebug
 *
 * Controls debug output.
 * true = debug information is printed to a log file.
 * false = no debug information is printed.
 * default is false
 */
$MathDebug = false;


/**
 * $MathDotsPerInch
 *
 * Controls dpi for MathLaTeXRender::DviPNGrender output.
 * Default 120
 * Article author can set this in attributes
 */
$MathDotsPerInch = false;


/**
 * $MathImageExt
 *
 * Image type used as defined by its extension.
 * This is set in MathLaTeXHooks:setup
 */
$MathImageExt = "png";


/**
 * $MathNameTag
 * 
 */
$MathNameTag = "MW_MATH";


/**
 * $MathTempPath
 *
 * The temporary directory for image creation
 */
$MathTempPath = false;


/**
 * $NamespaceWhiteList
 *
 * Namespaces, by id, where MathLaTeX is allowed to operate.
 * Main, id 0, is the default.
 */
 $NamespaceWhiteList = array( '0' );
 

/**
 * $PHPpath
 *
 * php.exe location in xampp
 * Default setting is false.
 */
$PHPpath = false;


$wgExtensionFunctions[] = 'MathLaTeX::setup';
$wgHooks['PageContentSave'][] = 'MathLaTeX::onPageContentSave';
$wgHooks['ParserFirstCallInit'][] = 'MathLaTeX::onParserFirstCallInit';
$wgHooks['EditPageBeforeEditToolbar'][] = 'MathLaTeX::onEditPageBeforeEditToolbar';


$wgAutoloadClasses['MathLaTeX'] = __DIR__ . '/' . 'MathLaTeX.body.php';
$wgAutoloadClasses['MathLaTeXRepository'] = __DIR__ . '/' . 'MathLaTeXRepository.php';
$wgAutoloadClasses['MathLaTeXRender'] = __DIR__ . '/' . 'MathLaTeXRender.php';

# Special Settings
$wgAutoloadClasses['SpecialMathLaTeX'] = __DIR__ . '/' . 'SpecialMathLaTeX.body.php';

# wgMessage Settings
$wgMessageDirs['MathLaTeX'] = __DIR__ . '/' . 'i18n';
$wgExtensionMessagesFiles['MathLaTeX'] = __DIR__ . '/MathLaTeX.i18n.php';
$wgExtensionMessagesFiles['MathLaTeXAlias'] = __DIR__ . '/MathLaTeX.alias.php';

# edit button
$wgResourceModules['ext.mathlatex.editbutton.enabler'] = array(
	'scripts' => 'modules/ext.mathlatex.editbutton.js',
	'messages' => array(
		'math_tip',
		'math_sample',
	),
 	'localBasePath' => __DIR__,
	'remoteExtPath' => 'mathlatex'
);
?>
