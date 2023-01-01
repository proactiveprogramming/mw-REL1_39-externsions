<?php
/**
 * OneColumnAllPages MediaWiki extension.
 *
 * Written by Leucosticte
 * https://www.mediawiki.org/wiki/User:Leucosticte
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup Extensions
 */

if( !defined( 'MEDIAWIKI' ) ) {
        echo( "This file is an extension to the MediaWiki software and cannot be used standalone.\n" );
        die( 1 );
}

$wgExtensionCredits['specialpage'][] = array(
        'path' => __FILE__,
        'name' => 'OneColumnAllPages',
        'author' => 'Nathan Larson',
        'url' => 'https://mediawiki.org/wiki/Extension:OneColumnAllPages',
        'descriptionmsg' => 'onecolumnallpages-desc',
        'version' => '1.0.2'
);

$wgExtensionMessagesFiles['OneColumnAllPages'] = __DIR__ . '/OneColumnAllPages.i18n.php';
$wgAutoloadClasses['SpecialOneColumnAllPages'] = __DIR__ . '/SpecialOneColumnAllPages.php';
$wgSpecialPages['OneColumnAllPages'] = 'SpecialOneColumnAllPages';
$wgSpecialPageGroups['OneColumnAllPages'] = 'other';
$wgOneColumnAllPagesRobotPolicy = 'index,follow';
