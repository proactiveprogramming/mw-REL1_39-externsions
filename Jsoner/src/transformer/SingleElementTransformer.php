<?php

namespace jsoner\transformer;

use jsoner\Helper;

class SingleElementTransformer extends AbstractTransformer
{
	public function transformZero( $options ) {
		return Helper::errorMessage( "No elements to transform.", [__METHOD__, $options] );
	}

	public function transformOne( $json, $options ) {
		$valueToSelect = $options;

		if ( is_array( $json[0] ) ) {
			return $json[0][$valueToSelect];
		}

		return $json[$valueToSelect];
	}

	public function transformMultiple( $json, $options ) {
		$count = count( $json );
		return Helper::errorMessage( "Got got multiple entries ($count)!", $options );
	}
}
