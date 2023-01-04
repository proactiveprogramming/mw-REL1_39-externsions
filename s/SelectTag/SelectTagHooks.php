<?php

class SelectTagHooks {
	/**
	 * @param Parser $parser
	 */
	public static function onSelectTagParserInit( Parser $parser ) {
		$parser->setHook( 'select', [ __CLASS__, 'onSelectTagRender' ] );
	}

	/**
	 * @param string $input
	 * @param array $params
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @return string
	 */
	public static function onSelectTagRender(
		string $input,
		array $params,
		Parser $parser,
		PPFrame $frame
	) {
		global $wgSelectTag;

		$parser->getOutput()->updateCacheExpiry( 0 );
		$dbr = wfGetDB( DB_REPLICA );
		if ( isset( $params['_source'] ) ) {
			$sourcearray = $wgSelectTag[$params['_source']];
		} else {
			return '<div style="color: red;">' .
				wfMessage( 'selecttag-sourceattr-unspecified' )
					->inContentLanguage()
					->escaped() . '</div>';
		}

		$dbtable    = $sourcearray['_dbname'];
		$cond       = '';
		$cond_array = [];

		if ( isset( $params['_show'] ) ) {
			$show = $sourcearray['_show'][$params['_show']];
		} else {
			$show = $sourcearray['_show'][$sourcearray['_showDefault']];
		}
		array_push( $cond_array, $show );

		foreach ( $sourcearray as $key => $value ) {
			if ( !is_array( $value ) && substr( $value, 0, 0 ) != '_' ) {
				if ( isset( $params[$key] ) ) {
					$params[$key] = $parser->recursiveTagParse( $params[$key], $frame );
					if ( strpos( $params[$key], '-' ) > -1 ) {
						$values_array = explode( '-', $params[$key] );
						$cond_value   = $value . ' BETWEEN ' . $values_array[0] . ' AND ' . $values_array[1];
						unset( $values_array );
					} else {
						$params[$key] = $dbr->addQuotes( $params[$key] );
						$cond_value = $value . '=' . $params[$key] . '';
					}

					if ( $cond == '' ) {
						$cond = $cond_value;
					} else {
						$cond .= ' AND ' . $cond_value;
					}

					array_push( $cond_array, $value );
				}
			}
		}

		$res          = $dbr->select( $dbtable, $cond_array, $cond, __METHOD__ );
		$return_value = '';
		foreach ( $res as $row ) {
			if ( $return_value == '' ) {
				$return_value = $row->$show;
			} else {
				$return_value .= "<br />" . $row->$show;
			}
		}

		return $return_value;
	}

}
