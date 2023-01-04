<?php

namespace jsoner\transformer;

use jsoner\Helper;

class InlineListTransformer extends AbstractTransformer
{
	public function transformZero( $options ) {
		return Helper::errorMessage( "No elements to transform.", [__METHOD__, $options] );
	}

	public function transformOne( $json, $options ) {
		$this->transformMultiple( $json, $options );
	}

	public function transformMultiple( $json, $options ) {
		$keyToJoin = $options;

		$selectedValues = [];
		foreach ( $json as $item ) {
			$value = $item[$keyToJoin];

			if ( $value !== null ) {
				$selectedValues[] = $value;
			}
		}

		return implode( ", ", $selectedValues );
	}
}
