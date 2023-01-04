<?php

/**
 * @file
 *
 * @ingroup Extensions
 * @ingroup Extensions-DynamicPageListEngine
 *
 * @author [RV1971](https://www.mediawiki.org/wiki/User:RV1971)
 */

/**
 * Selection by redirection to specified pages.
 *
 * Recognizes the parameters `redirectsto` and `notredirectsto`. Each of them
 * may be a string or an array.
 *
 * @ingroup Extensions
 * @ingroup Extensions-DynamicPageListEngine
 */
class DpleFeatureRedirectsto extends DpleFeatureLinksBase
implements DpleFeatureInterface {
	/**
	 * @param array $params
	 * @param array &$features
	 */
	public function __construct( array $params, array &$features ) {
		parent::__construct(
			$params, $features,
			'redirectsto', NS_MAIN,
			'redirect', 'rd', 'rd_namespace',
			[ 'page_id = $table.rd_from',
			  '$table.rd_namespace = $ns',
			  '$table.rd_title = $dbkey' ] );
	}

	/**
	 * Modify a given query.
	 * @see DpleFeatureBase::modifyQuery()
	 *
	 * @param DpleQuery &$query
	 */
	public function modifyQuery( DpleQuery &$query ) {
		parent::modifyQuery( $query );

		/** For efficiency, limit selection to redirects. */
		if ( $this->linkedCount_ ) {
			$query->addConds( [ 'page_is_redirect' => 1 ] );
		}
	}
}
