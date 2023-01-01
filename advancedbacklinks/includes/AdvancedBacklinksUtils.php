<?php

use MediaWiki\MediaWikiServices;
use MediaWiki\Page\PageIdentity;

class AdvancedBacklinksUtils {

	/**
	 * Get direct link statistics for a title.
	 * @param PageIdentity $page
	 * @param bool $direct
	 * @param bool $contentOnly
	 * @param bool $redirects
	 * @param bool $throughRedirects
	 * @return array
	 */
	public static function GetLinkStats( PageIdentity $page, bool $direct, bool $contentOnly,
			bool $redirects = false, bool $throughRedirects = true ) {

		$dbr = wfGetDB( DB_REPLICA );
		$incomingConditions = [
			'abl_namespace' => $page->getNamespace(),
			'abl_title' => $page->getDBkey()
		];
		$trConditions = [
			'rd_title' => $page->getDBkey(),
			'rd_namespace' => $page->getNamespace(),
			'page_id = rd_from',
			'abl_title = page_title',
			'abl_namespace = page_namespace'
		];
		$incomingTables = [ 'ab_links' ];
		$outgoingConditions = [
			'abl_from' => $page->getArticleID()
		];

		if ( $direct ) {

			$incomingConditions['abl_through'] = 0;
			$trConditions['abl_through'] = 0;
			$outgoingConditions['abl_through'] = 0;
		}

		if ( $contentOnly ) {
			$config = MediaWikiServices::getInstance()->getMainConfig();
			$contentNamespaces = $config->get( 'ContentNamespaces' );
			$incomingConditions['abl_from_namespace'] = $contentNamespaces;
			$trConditions['abl_from_namespace'] = $contentNamespaces;
			$outgoingConditions['abl_namespace'] = $contentNamespaces;
		}

		if ( !$redirects ) {
			$incomingConditions[] = 'page_id = abl_from';
			$incomingConditions['page_is_redirect'] = 0;
			$incomingTables[] = 'page';
		}

		$incoming = $dbr->selectField(
			$incomingTables,
			'COUNT(abl_from)',
			$incomingConditions,
			__METHOD__
		);

		// links through redirects
		if ( $throughRedirects ) {
			$incoming += $dbr->selectField(
				[ 'redirect', 'page', 'ab_links' ],
				'COUNT(abl_from)',
				$trConditions
			);
		}

		$outgoing = $dbr->selectField(
			'ab_links',
			'COUNT(*)',
			$outgoingConditions,
			__METHOD__
		);

		return [ 'in' => $incoming, 'out' => $outgoing ];
	}
}
