<?php

/**
 * AbuseFilter hooks
 */
class AbAfHooks {

	/**
	 * Tells AbuseFilter about our variables
	 *
	 * @param array &$builderValues
	 * @return void
	 */
	public static function onAbuseFilterBuilder( array &$builderValues ) {
		$builderValues['vars']['back_templatelinks'] = 'back-templatelinks';
	}

	/**
	 * Sets lazy-loaded vars
	 *
	 * @param AbuseFilterVariableHolder $vars
	 * @param Title $title
	 * @param string $prefix
	 * @return void
	 */
	public static function onAbuseFilterGenerateTitleVars(
		AbuseFilterVariableHolder $vars,
		Title $title,
		string $prefix
	) {
		$vars->setLazyLoadVar(
			'back_templatelinks',
			'back-templatelinks',
			[ 'title' => $title ]
		);
	}

	/**
	 * Computes the variables
	 *
	 * @param string $method
	 * @param AbuseFilterVariableHolder $vars
	 * @param array $parameters
	 * @param &$result
	 * @return bool
	 */
	public static function onAbuseFilterComputeVariable(
		string $method,
		AbuseFilterVariableHolder $vars,
		array $parameters,
		&$result
	) {
		if ( $method === 'back-templatelinks' ) {
			$title = $parameters['title'] ?? false;
			if ( !$title instanceof Title ) {
				return false;
			}

			$dbr = wfGetDB( DB_REPLICA );
			$result = intval( $dbr->selectField(
				'templatelinks',
				'count(tl_from)',
				[
					'tl_namespace' => $title->getNamespace(),
					'tl_title' => $title->getDBkey()
				]
			) );

			return false;
		} else {
			return true;
		}
	}
}