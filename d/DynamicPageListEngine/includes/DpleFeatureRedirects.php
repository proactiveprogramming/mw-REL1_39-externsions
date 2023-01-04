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
 * Include or exclude redirects.
 *
 * Recognizes the parameter `redirects`, which may be one of
 * `exclude|include|only`. Default is `exclude`, for compatibility
 * with
 * [Extension:DynamicPageList](https://www.mediawiki.org/wiki/Extension:DynamicPageList_(Wikimedia%29).
 * This implies that enabling this feature in @ref
 * $wgDpleFeatures may change the result set of a
 * dynamic page list even for parameter sets which do not contain the
 * `redirects` parameter.
 *
 * @ingroup Extensions
 * @ingroup Extensions-DynamicPageListEngine
 */
class DpleFeatureRedirects extends DpleFeatureBase
implements DpleFeatureInterface {

	/** include|only|exclude. */
	private $redirects_;

	/** Whether to resolve redirects. */
	private $resolve_;

	/**
	 * @param array $params
	 * @param array &$features
	 */
	public function __construct( array $params, array &$features ) {
		parent::__construct( $features );

		if ( isset( $params['redirects'] )
			&& $params['redirects'] == 'resolve' ) {
			$this->resolve_ = true;
			$params['redirects'] = 'only';
		}

		$this->redirects_ = $this->parseIncludeExclude(
			isset( $params['redirects'] ) ? $params['redirects'] : null );
	}

	public function getRedirects() {
		return $this->redirects_;
	}

	public function ifResolve() {
		return $this->resolve_;
	}

	/**
	 * Modify a given query.
	 * @see DpleFeatureBase::modifyQuery()
	 *
	 * @param DpleQuery &$query
	 */
	public function modifyQuery( DpleQuery &$query ) {
		switch ( $this->redirects_ ) {
			case 'only':
				$query->addConds( [ 'page_is_redirect' => 1 ] );
				break;

			case 'exclude':
				$query->addConds( [ 'page_is_redirect' => 0 ] );
				break;
		}
	}
}
