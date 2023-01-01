<?php

use MediaWiki\MediaWikiServices;

/**
 * AdvancedBacklinks
 * Copyright (C) 2019  Ostrzyciel
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

class AdvancedBacklinksHooks {

	const AB_CHANGE_LINK_PREF = 'ab-change-link-to-whatlinkshere';

	/**
	 * LinksUpdate handler.
	 * @param LinksUpdate $linksUpdate
	 * @throws Exception
	 */
	public static function onLinksUpdate( LinksUpdate &$linksUpdate ) {

		$title = $linksUpdate->getTitle();
		if ( !isset( $linksUpdate->getParserOutput()->abSet ) ) {
			wfDebugLog( 'AdvancedBacklinks', "abSet not found on $title" );
			$abSet = new AdvancedLinkSet();
		} else {
			$abSet = $linksUpdate->getParserOutput()->abSet;
		}

		foreach ( $linksUpdate->getParserOutput()->getLinks() as $ns => $links ) {
			foreach ( $links as $dbKey => $id ) {
				if ( $abSet->maybeAddMissingWikilink( $title, $ns, $dbKey ) ) {
					wfDebugLog( 'AdvancedBacklinks', "Added missing wikilink from $title to $dbKey in ns $ns" );
				}
			}
		}

		foreach ( array_keys( $linksUpdate->getParserOutput()->getImages() ) as $dbKey ) {
			if ( $abSet->maybeAddMissingImagelink( $title, $dbKey ) ) {
				wfDebugLog( 'AdvancedBacklinks', "Added missing imagelink from $title to $dbKey" );
			}
		}

		try {
			wfDebugLog( 'AdvancedBacklinks', "LinksUpdate called on $title" );
			$abSet->updateLinksFromPageInDB( $title );
		} catch ( Exception $ex ) {
			wfDebugLog( 'AdvancedBacklinks', 'Error updating ab_links: ' .
				$ex->getMessage()
			);
		}
	}

	/**
	* Adds the required table storing votes into the database when the
	* end-user (sysadmin) runs /maintenance/update.php
	*
	* @param DatabaseUpdater $updater
	*/
	public static function onLoadExtensionSchemaUpdates( $updater ) {
		$patchPath = __DIR__ . '/../sql/';

		$updater->addExtensionTable( 'ab_links', $patchPath . 'create-table--ab-links.sql' );
		$updater->addExtensionTable( 'ab_images', $patchPath . 'create-table--ab-images.sql' );
		$updater->addExtensionTable( 'ab_undesired', $patchPath . 'create-table--ab-undesired.sql' );

		// for 2.6
		$updater->addExtensionField(
			'ab_links',
			'abl_hidden_through',
			$patchPath . 'update/add-field--abl-hidden-through.sql'
		);
		$updater->addExtensionIndex(
			'ab_links',
			'abl_composite_hidden',
			$patchPath . 'update/add-index--abl-composite-hidden.sql'
		);
	}

	/**
	 * Changes the "whatlinkshere" link in sidebar to point to Special:AdvancedBacklinks.
	 *
	 * @param Skin $skin
	 * @param $sidebar
	 *
	 * @throws MWException
	 */
	public static function onSidebarBeforeOutput( Skin $skin, &$sidebar ) {
		$link = self::makeSidebarLink( $skin );
		if ( $link ) {
			$sidebar['TOOLBOX']['whatlinkshere']['href'] = $link;
		}
	}

	/**
	 * @return bool|string
	 * @throws MWException
	 */
	private static function makeSidebarLink( Skin $skin ) {
		$optionsLookup = MediaWikiServices::getInstance()->getUserOptionsLookup();
		if ( !$optionsLookup->getBoolOption(
			$skin->getUser(), self::AB_CHANGE_LINK_PREF
		) ) {
			return false;
		}

		$title = $skin->getTitle();
		if ( !$title ) {
			return false;
		}

		if ( $title->getNamespace() < 0 ) {
			$subpage = $title->getText();
			$ix = strpos( $subpage, '/' );
			if ( $ix === false ) {
				return false;
			}

			$subpage = substr( $subpage, $ix + 1 );
			if ( $subpage == $title->getText() ) {
				return false;
			}

			$title = Title::newFromText( $subpage );
			if ( !$title || $title->getNamespace() < 0 ) {
				return false;
			}
		}

		return SpecialPage::getTitleFor( 'AdvancedBacklinks', $title->getPrefixedText() )->getFullURL();
	}

	/**
	 * @param array &$doubleUnderscoreIDs
	 */
	public static function onGetDoubleUnderscoreIDs( &$doubleUnderscoreIDs ) {
		$doubleUnderscoreIDs = array_merge( $doubleUnderscoreIDs, [
			'directlink',
			'redlinkallergic',
			'ignoreorphaned',
			'redlinkallergicthrough',
			'transclusionisnotadoption',
		] );
	}

	/**
	 * Cleans up old ab_links and ab_images rows after page deletion.
	 * This method DOES NOT remove links going through this page - this should be done later during LinksUpdate.
	 * @param $article
	 * @param User $user
	 * @param $reason
	 * @param $id
	 * @param $content
	 * @param LogEntry $logEntry
	 * @param $archivedRevisionCount
	 */
	public static function onArticleDeleteComplete(
		&$article, User &$user, $reason, $id, $content, LogEntry $logEntry, $archivedRevisionCount ) {

		$dbw = wfGetDB( DB_PRIMARY );
		$dbw->delete(
			'ab_links',
			[ 'abl_from' => $id ],
			__METHOD__
		);

		$dbw->delete(
			'ab_images',
			[ 'abi_from' => $id ],
			__METHOD__
		);
	}

	/**
	 * Makes all the lonelypages reports ignore pages with __IGNOREORPHANED__
	 *
	 * @param $tables
	 * @param $conds
	 * @param $joinConds
	 */
	public static function onLonelyPagesQuery( &$tables, &$conds, &$joinConds ) {
		$tables['ppo'] = 'page_props';
		$conds[] = 'ppo.pp_value IS NULL';
		$joinConds['ppo'] = [
			'LEFT JOIN', [
				'ppo.pp_page = page_id',
				'ppo.pp_propname' => 'ignoreorphaned'
			]
		];
	}

	/**
	 * Registers this extension's query pages so they can be updated by the updateSpecialPages.php script.
	 *
	 * @param $wgQueryPages
	 */
	public static function onwgQueryPages( &$wgQueryPages ) {
		$wgQueryPages[] = [ SpecialWikitextWantedPages::class, 'WikitextWantedPages' ];
		$wgQueryPages[] = [ SpecialWikitextLonelyPages::class, 'WikitextLonelyPages' ];
		$wgQueryPages[] = [ SpecialWikitextContentLonelyPages::class, 'WikitextContentLonelyPages' ];
		$wgQueryPages[] = [ SpecialMostWikitextLinked::class, 'MostWikitextLinked' ];
		$wgQueryPages[] = [ SpecialMostWikitextLinkedFiles::class, 'MostWikitextLinkedFiles' ];
		$wgQueryPages[] = [ SpecialUndesiredRedlinks::class, 'UndesiredRedlinks' ];
	}

	/**
	 * Registers this extension's user preferences
	 *
	 * @param $user
	 * @param $preferences
	 */
	public static function onGetPreferences( $user, &$preferences ) {
		$preferences[self::AB_CHANGE_LINK_PREF] = [
			'type' => 'check',
			'label-message' => 'advancedBacklinks-pref-change-link', // a system message
			'section' => 'rendering/advancedbacklinks',
			//'default' => 0
		];
	}
}