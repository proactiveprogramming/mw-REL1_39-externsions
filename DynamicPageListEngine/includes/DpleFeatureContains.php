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
 * Selection of categories which contain specified pages.
 *
 * Recognizes the parameters `contains` and `notcontains`. Each of
 * them may be a string or an array.
 *
 * @ingroup Extensions
 * @ingroup Extensions-DynamicPageListEngine
 */
class DpleFeatureContains extends DpleFeatureLinksBase
implements DpleFeatureInterface {
	/**
	 * @param array $params
	 * @param array &$features
	 */
	public function __construct( array $params, array &$features ) {
		parent::__construct(
			$params, $features,
			'contains', NS_MAIN,
			'categorylinks', 'clx', 'cl_to',
			[ 'page_title = $table.cl_to',
			  'page_namespace = ' . NS_CATEGORY,
			  '$table.cl_from = $id' ] );
	}

	/**
	 * Modify a given query.
	 * @see DpleFeatureBase::modifyQuery()
	 *
	 * @param DpleQuery &$query
	 */
	public function modifyQuery( DpleQuery &$query ) {
		parent::modifyQuery( $query );

		/** Also select timestamp if at least one page is specified. */
		if ( $this->getLinkedCount() ) {
			$query->addVars( [ 'clx_timestamp' => 'clx1.cl_timestamp' ] );
		}
	}
}
