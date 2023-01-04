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
 * Limit the number of records to fetch from the database.
 *
 * Recognizes the parameter `count` which limits the number of records
 * to fetch. Invalid values (including 0) are interpreted as 1, for
 * compatibility with
 * [Extension:DynamicPageList](https://www.mediawiki.org/wiki/Extension:DynamicPageList_(Wikimedia%29).
 * In any case, the number is limited to @ref
 * $wgDpleMaxResultCount.
 *
 * @ingroup Extensions
 * @ingroup Extensions-DynamicPageListEngine
 */
class DpleFeatureCount extends DpleFeatureBase
implements DpleFeatureInterface {

	/** Whether the global configuration has been initialized. */
	private static $intitialized_ = false;

	/** Initialize the global configuration. */
	public static function initConf() {
		if ( self::$intitialized_ ) {
			return;
		}

		self::$intitialized_ = true;

		global $wgDpleMaxResultCount;
		global $wgDLPMaxResultCount, $wgDLPAllowUnlimitedResults;

		/**
		 * If the global configuration variable @ref
		 * $wgDpleMaxResultCount is unset, initialize it with the
		 * corresponding configuration from
		 * [Extension:DynamicPageList](https://www.mediawiki.org/wiki/Extension:DynamicPageList_(Wikimedia%29);
		 * if the latter is unset as well, initialize with the
		 * defaults from Extension:DynamicPageList.
		 *
		 * For simplicity of implementation, 'unlimited' is
		 * implemented just with unrealistically large numbers.
		 */

		if ( !isset( $wgDpleMaxResultCount ) ) {
			if ( isset( $wgDLPAllowUnlimitedResults )
				&& $wgDLPAllowUnlimitedResults ) {
				$wgDpleMaxResultCount = 100000;
			} elseif ( isset( $wgDLPMaxResultCount ) ) {
				$wgDpleMaxResultCount = $wgDLPMaxResultCount;
			} else {
				$wgDpleMaxResultCount = 200;
			}
		}
	}

	/** @var int Maxium number of records to fetch. */
	private $count_;

	/**
	 * @param array $params
	 * @param array &$features
	 */
	public function __construct( array $params, array &$features ) {
		self::initConf();

		parent::__construct( $features );

		$this->count_ = $this->parse( isset( $params['count'] )
			? $params['count'] : null );
	}

	public function getCount() {
		return $this->count_;
	}

	/**
	 * Parse a count specification.
	 *
	 * @param int|string $param Parameter value.
	 *
	 * @return int Count.
	 */
	public function parse( $param ) {
		global $wgDpleMaxResultCount;

		if ( isset( $param ) ) {
			$count = intval( $param );

			if ( $count < 1 ) {
				/** Return 1 if $param is less then 1 (including the
				 *	case that it is invalid).
				 */
				$count = 1;
			} elseif ( $count > $wgDpleMaxResultCount ) {
				/** Return @ref $wgDpleMaxResultCount
				 *	if a larger value than that was requested.
				 */
				$count = $wgDpleMaxResultCount;
			}
		} else {
			$count = $wgDpleMaxResultCount;
		}

		return $count;
	}

	/**
	 * Modify a given query.
	 * @see DpleFeatureBase::modifyQuery()
	 *
	 * @param DpleQuery &$query
	 */
	public function modifyQuery( DpleQuery &$query ) {
		/** Set the LIMIT clause. */
		$query->setOption( 'LIMIT', $this->count_ );
	}
}
