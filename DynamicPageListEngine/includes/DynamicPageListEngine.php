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
 * Dynamic page list backend.
 *
 * You can use this class as a data source for your own extensions.
 *
 * @ingroup Extensions
 * @ingroup Extensions-DynamicPageListEngine
 */
class DynamicPageListEngine implements Countable {

	/** Array of parameters given to the constructor. */
	private $params_ = [];

	/** Array of objects derived from DpleFeature. */
	private $features_ = [];

	/** Mapping of result converter functions to the feature classes they are defined in. */
	private $converters_ = [];

	/** DpleQuery object */
	private $query_;

	/**
	 * Evaluates parameters and fetches records from the database.
	 *
	 * @param array $params Associative array of parameters.
	 */
	public function __construct( array $params ) {
		global $wgDpleFeatures;

		$this->params_ = $params;

		/** Construct all enabled features, regardless of whether they
		 *	are relevant for a particular request. This simplifies
		 *	feature development since the features do not need to
		 *	provide information of what they are relevant for.
		 */
		foreach ( $wgDpleFeatures as $class => $enabled ) {
			if ( $enabled ) {
				$this->features_[$class] = new $class(
					$this->params_, $this->features_ );

				/** Register all result converters. */
				foreach ( $this->features_[$class]->getResultConverters()
						  as $method ) {
					$this->converters_[$method] = $class;
				}
			}
		}

		$this->query_ = new DpleQuery(
			'page',
			[ 'page_namespace', 'page_title', 'page_is_redirect', 'page_len' ] );

		/** Let all features modify @ref $query_. */
		foreach ( $this->features_ as $feature ) {
			$feature->modifyQuery( $this->query_ );
		}

		/** Execute the query. */
		$this->query_->execute();

		wfDebug( __METHOD__ . ': ' . count( $this ) . " records\n" );
	}

	/**
	 * @return int Number of result rows, or 0 if the query has not
	 * yet been executed.
	 */
	public function count() {
		return count( $this->query_ );
	}

	public function &getFeatures() {
		return $this->features_;
	}

	/**
	 * Get a specific feature.
	 *
	 * @param string $class The feature class.
	 *
	 * @return A reference to the feature object, if there is one,
	 * else NULL.
	 */
	public function &getFeature( $class ) {
		static $unset;

		if ( isset( $this->features_[$class] ) ) {
			return $this->features_[$class];
		} else {
			return $unset;
		}
	}

	public function getQuery() {
		return $this->query_;
	}

	/**
	 * Get the query result.
	 *
	 * To access the ResultWrapper object without any conversion, use
	 * getQuery()->getResult().
	 *
	 * @param string $method Method to convert the query result.
	 *
	 * @return Return value of the converter applied to the query
	 * result.
	 */
	public function getResult( $method = 'toTitles' ) {
		return call_user_func(
			[ $this->features_[$this->converters_[$method]], $method ],
			$this->query_->getResult() );
	}
}
