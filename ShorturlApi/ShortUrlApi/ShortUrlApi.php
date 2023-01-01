<?php
/**
 * @ingroup Extensions
 * @{
 * MediaWiki extension providing an API for the ShortUrl extension
 *
 * @file
 * @{
 * @copyright © 2014 Daniel Norton d/b/a WeirdoSoft - www.weirdosoft.com
 *
 * @section License
 * **GPL v3**\n
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * \n\n
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * \n\n
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 * @}
 *
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	echo "This file is an extension to MediaWiki software and is not designed for standalone use.\n";
	die( 1 );
}

if ( defined( 'MW_EXT_SHORTURLAPI_NAME' ) ) {
	echo "Extension module already loaded: " . MW_EXT_SHORTURLAPI_NAME . "\n";
	die ( 1 );
}

define( 'MW_EXT_SHORTURLAPI_NAME',            'ShortUrlApi' );
define( 'MW_EXT_SHORTURLAPI_VERSION',         '1.0.2' );
define( 'MW_EXT_SHORTURLAPI_AUTHOR',          'Daniel Norton' );

define( 'MW_EXT_SHORTURLAPI_PARAM_NAME',      'shorturl' );
define( 'MW_EXT_SHORTURLAPI_API_MID',         'su' );

define( 'MW_EXT_SHORTURLAPI_API_CLASS',       'ApiShortUrl' );
define( 'MW_EXT_SHORTURLAPI_API_QUERY_CLASS', 'ApiQueryShortUrl' );

global $wgAPIModules, $wgAPIPropModules, $wgAutoloadClasses, $wgExtensionCredits;

$wgExtensionCredits['api'][] = array(
	'path' => __DIR__ . '/' . MW_EXT_SHORTURLAPI_NAME,
	'name'         => MW_EXT_SHORTURLAPI_NAME,
	'description'  => 'Provide information about MediaWiki ShortUrl objects.',
	'version'      => MW_EXT_SHORTURLAPI_VERSION,
	'author'       => MW_EXT_SHORTURLAPI_AUTHOR,
	'license-name' => '[https://www.gnu.org/licenses/gpl-3.0.txt GPL v3]',
	'url'          => 'https://www.mediawiki.org/wiki/Extension:ShortUrlApi',
);

// API declarations

// action=query&prop=shorturl
$wgAPIPropModules[MW_EXT_SHORTURLAPI_PARAM_NAME] = MW_EXT_SHORTURLAPI_API_QUERY_CLASS;
$wgAutoloadClasses[MW_EXT_SHORTURLAPI_API_QUERY_CLASS] =
	 __DIR__ . '/' . MW_EXT_SHORTURLAPI_API_QUERY_CLASS . '.php';

// action=shorturl
$wgAPIModules[MW_EXT_SHORTURLAPI_PARAM_NAME] = MW_EXT_SHORTURLAPI_API_CLASS;
$wgAutoloadClasses[MW_EXT_SHORTURLAPI_API_CLASS] =
	 __DIR__ . '/' . MW_EXT_SHORTURLAPI_API_CLASS . '.php';

/** @}*/
