<?php

use MediaWiki\MediaWikiServices;

class OnlyRecentRecentChangesHooks {

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ChangesListSpecialPageQuery
	 * @param string $name name of the special page, e.g. 'Watchlist'
	 * @param array &$tables array of tables to be queried
	 * @param array &$fields array of columns to select
	 * @param array &$conds array of WHERE conditionals for query
	 * @param array &$query_options array of options for the database request
	 * @param array &$join_conds join conditions for the tables
	 * @param \FormOptions $opts FormOptions for this request
	 */
	public static function onChangesListSpecialPageQuery(
		$name,
		&$tables,
		&$fields,
		&$conds,
		&$query_options,
		&$join_conds,
		$opts
	) {
		if ( MediaWikiServices::getInstance()->getUserOptionsManager()->getOption(
			RequestContext::getMain()->getUser(),
			'onlyrecentrecentchanges-show-only-recent-change' )
		) {
			$dbr = wfGetDB( DB_REPLICA );

			if ( !in_array( 'page', $tables ) ) {
				array_unshift( $tables, 'page' );
			}

			$conds[] = $dbr->makeList(
				[
					'page_latest = rc_this_oldid',
					'rc_log_action != ' . $dbr->addQuotes( '' )
				],
				LIST_OR
			);

			$join_conds['page'] = [
				'LEFT JOIN',
				'page_latest = rc_this_oldid'
			];
		}
	}

	/**
	 * @param User $user
	 * @param array[] &$preferences
	 */
	public static function onGetPreferences( $user, &$preferences ) {
		$preferences['onlyrecentrecentchanges-show-only-recent-change'] = [
			'section' => 'rc/advancedrc',
			'type' => 'toggle',
			'label-message' => 'onlyrecentrecentchanges-option'
		];
	}
}
