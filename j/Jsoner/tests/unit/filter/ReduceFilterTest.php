<?php

namespace jsoner\filter;

use jsoner\ArrayCompareHelper;
use jsoner\Parser;

class ReduceFilterTest extends \PHPUnit_Framework_TestCase
{
	public function testDataProvider() {
		return [
			[
				['firstname', 'name.first'],
				'[{"name":{"first":"Bill","last":"Gates"},"email":"testuser@example.com","age":7357},{"name":{"first":"Steve","last":"Wozniak"},"email":"testuser2@example.com","age":73571}]',
				'[{"name":{"first":"Bill","last":"Gates"},"firstname":"Bill","email":"testuser@example.com","age":7357},{"name":{"first":"Steve","last":"Wozniak"},"firstname":"Steve","email":"testuser2@example.com","age":73571}]',
				'Did not fetch name.first (nested) as firstname (elementwise top-level).'
			]
		];
	}

	/**
	 * @dataProvider testDataProvider
	 */
	public function testFilter( $filterParams, $input, $expected, $message = '' ) {
		$input = Parser::jsonDecode( $input );
		$expected = Parser::jsonDecode( $expected );

		$output = ReduceFilter::doFilter( $input, $filterParams );

		$this->assertTrue( $this->arrayEquals( $expected, $output ),
			'The ReduceFilter ' . lcfirst( $message ) . '.'
		);
	}

	private function arrayEquals( $a, $b ) {

		$this->recur_ksort( $a );
		$this->recur_ksort( $b );
		return $a === $b;
	}

	private function recur_ksort( &$array ) {
		foreach ( $array as &$value ) {
			if ( is_array( $value ) ) { $this->recur_ksort( $value );
	  }
		}
		return ksort( $array );
	}
}
