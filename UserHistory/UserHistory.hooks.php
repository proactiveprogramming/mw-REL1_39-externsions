<?php
/**
 * Hooks for UserHistory extension
 *
 * @ingroup Extensions
 */

class UserHistoryHooks {

	/**
	 * UserHistory extension only tracks accessed articles (ignoring "special/other" pages), thus
	 * it can hook into ArticleFromTitle hook which only be invoked once per page.
	 * Log the access timestamp, user and the accessed page.
	 *
	 * See MediaWiki's ArticleFromTitle hook for more information.
	 *
	 * @param Title          $title
	 * @param string         $article
	 * @param RequestContext $context
	 */
	public static function onArticleFromTitle( Title &$title, &$article, RequestContext $context ) {
		// only track logged in users
		if ( !wfReadOnly() && $context->getUser()->isLoggedIn() ) {
			$dbw = wfGetDB( DB_MASTER );
			$dbw->insert( 'user_history', [
				'uh_user_id'   => $context->getUser()->getId(),
				'uh_page_id'   => $title->getArticleID(),
				'uh_timestamp' => $dbw->timestamp()
			] );
		}
	}

	/**
	 * Setup "user_history" extension table.
	 * This callback method is called when LoadExtensionSchemaUpdates hook is invoked during updates.
	 *
	 * @param DatabaseUpdater $updater
	 *
	 * @return bool
	 */
	public static function onLoadExtensionSchemaUpdates( DatabaseUpdater $updater ) {
		$updater->addExtensionTable( 'user_history', __DIR__ . '/db/user_history.sql' );
		return true;
	}
}
