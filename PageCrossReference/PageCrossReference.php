<?php
/**
 *
 * @file
 * @name PageCrossReference
 * @version 0.062
 * @author Jesse B. Dooley
 * @date February 27, 2013
 * @brief On page save parse the page for other page titles in the wiki
 * and turn that raw text into an internal link.
 *
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
 * @see README.TXT
 * @see VERSION.TXT
 * @see GNU-GPL-3.0.txt
 *
 */

  if ( !defined( 'MEDIAWIKI' ) ) {
    die( 'Not an entry point.' );
    }

    // Configuration variables
    $wgPageCrossReferenceMinimumWordLength    = 2;        /*!< Choose titles with these or more words */
    $wgPageCrossReferenceSkipHeaders          = false;    /*!< Headers, true = ignore, false = search */
    $wgPageCrossReferenceBlackList            = array();  /*!< Never choose these titles if found, assume underscores for spaces */

    $wgExtensionCredits['parserhook'][] = array(
        'path'           => __FILE__,
        'name'           => 'PageCrossReference',
        'author'         => 'Jesse B. Dooley',
        'url'            => 'http://www.mediawiki.org/wiki/Extension:PageCrossReference',
        'descriptionmsg' => 'pagecrossreference-desc',
        'version'        => '0.062',
    );

    $wgExtensionMessagesFiles['PageCrossReference'] = dirname( __FILE__ ) . '\PageCrossReference.i18n.php';
    $wgAutoloadClasses['PageCrossReference'] = dirname(__FILE__) . '\PageCrossReference.body.php';
    $wgExtensionFunctions[] = 'PageCrossReference::PageCrossReferenceSetup';
#    $wgHooks['PageContentSaveComplete'][] = 'PageCrossReference::onPageContentSaveComplete';
    $wgHooks['ArticleSaveComplete'][] = 'PageCrossReference::onArticleSaveComplete';
?>
