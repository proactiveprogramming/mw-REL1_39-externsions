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
 * Include or exclude subpages.
 *
 * Recognizes the parameter `subpages`, which may be one of
 * `exclude|include|only`. Default is `exclude`, for consistency with
 * DpleFeatureRedirects.  This implies that enabling
 * this feature in @ref $wgDpleFeatures may change
 * the result set of a dynamic page list even for parameter sets which
 * do not contain the `subpages` parameter.
 *
 * Subpage selection works with a simple LIKE '%/%' expression,
 * regardless of whether the namespace of a page has subpages
 * enabled. To distinguish whether subpages are enabled, a CASE
 * expression or something similar would need to be evaluated for each
 * single row, and it would be difficult to implement this in an
 * efficient *and* portable way.
 *
 * @ingroup Extensions
 * @ingroup Extensions-DynamicPageListEngine
 */
class DpleFeatureSubpages extends DpleFeatureBase
implements DpleFeatureInterface {

	/** include|only|exclude. */
	private $subpages_;

	/**
	 * @param array $params
	 * @param array &$features
	 */
	public function __construct( array $params, array &$features ) {
		parent::__construct( $features );

		$this->subpages_ = $this->parseIncludeExclude(
			isset( $params['subpages'] ) ? $params['subpages'] : null );
	}

	public function getSubpages() {
		return $this->subpages_;
	}

	/**
	 * Modify a given query.
	 * @see DpleFeatureBase::modifyQuery()
	 *
	 * @param DpleQuery &$query
	 */
	public function modifyQuery( DpleQuery &$query ) {
		$dbr = $query->getDbr();

		switch ( $this->subpages_ ) {
			case 'only':
				$query->addConds( 'page_title'
					. $dbr->buildLike( $dbr->anyString(), '/',
						$dbr->anyString() ) );
				break;

			case 'exclude':
				$query->addConds( 'page_title NOT'
					. $dbr->buildLike( $dbr->anyString(), '/',
						$dbr->anyString() ) );
				break;
		}
	}
}
