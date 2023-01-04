<?php
/**
 * RecentChangesLogFilter extension hooks
 *
 * @file
 * @ingroup Extensions
 * @author Patrick Westerhoff <PatrickWesterhoff@gmail.com>
 */
class RecentChangesLogFilterHooks {
	/**
	 * Add the `hidelogs` filter to the recent changes filter list.
	 *
	 * @param $special ChangesListSpecialPage instance
	 * @param $filters associative array of filter definitions
	 * @return true
	 */
	public static function onChangesListSpecialPageFilters( $special, &$filters ) {
		if ($special->getName() !== 'Recentchanges') {
			return true;
		}

		$filters['hidelogs'] = array(
			'msg' => 'recentchangeslogfilter-hidelogs',
			'default' => $special->getUser()->getBoolOption( 'rchidelogs' )
		);

		return true;
	}

	/**
	 * Modify the recent changes query to hide log entries from the list. This only
	 * occurs when the `hidelogs` filter is enabled (which is the default).
	 *
	 * @param $name name of the special page, e.g. 'Watchlist'
	 * @param $tables array of tables to be queried
	 * @param $fields array of columns to select
	 * @param $conds array of WHERE conditionals for query
	 * @param $query_options array of options for the database request
	 * @param $join_conds join conditions for the tables
	 * @param $opts FormOptions for this request
	 * @return true
	 */
	public static function onChangesListSpecialPageQuery( $name, &$tables, &$fields, &$conds, &$query_options, &$join_conds, $opts ) {
		if ($name !== 'Recentchanges') {
			return true;
		}

		global $wgRecentChangesLogFilterTypes;
		$dbr = wfGetDB( DB_SLAVE );

		if (!$opts->validateName('hidelogs')) {
			global $wgDefaultUserOptions;
			$opts->add('hidelogs', $wgDefaultUserOptions['rchidelogs']);
		}

		if ($opts['hidelogs']) {
			$conditions = array();
			foreach ($wgRecentChangesLogFilterTypes as $type) {
				$conditions[] = 'rc_log_type != ' . $dbr->addQuotes( $type );
			}

			$conds[] = '( rc_log_type IS NULL OR ( ' . implode( ' AND ', $conditions ) . ' ) )';
		}

		return true;
	}

	/**
	 * Add a user preference to allow setting the default behavior.
	 *
	 * @param $user user whose preferences are being modified
	 * @param $preferences preferences description array
	 * @return true
	 */
	public static function onGetPreferences( User $user, array &$preferences ) {
		$preferences['rchidelogs'] = array(
			'type' => 'toggle',
			'label-message' => 'recentchangeslogfilter-pref',
			'section' => 'rc/advancedrc',
		);

		return true;
	}
}
