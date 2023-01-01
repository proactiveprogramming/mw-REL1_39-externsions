<?php

namespace jsoner\transformer;

use jsoner\Helper;

class StackedElementTransformer extends AbstractTransformer
{

	public function transformZero( $options ) {

		return Helper::errorMessage( "No elements to transform.", [__METHOD__, $options] );
	}

	public function transformOne( $json, $options ) {

		if ( is_array( $json[0] ) ) {
			$values = array_values( $json[0] );
		} else {
			$values = array_values( $json );
		}

		$result = '';
		foreach ( $values as $item ) {
			// NNIS-4567
			if (is_array($item)) {
				$item = join(", ", $item);
			}

			$result .= "<nowiki>$item</nowiki><br />";
		}

		return $result;
	}

	public function transformMultiple( $json, $options ) {

		$count = count( $json );
		return Helper::errorMessage( "Got got multiple entries ($count)!", $options );
	}
}
