<?php

namespace jsoner\filter;

use jsoner\Parser;

class CensorKeysFilterTest extends \PHPUnit_Framework_TestCase
{
	public function testDataProvider() {
		return [
			[
				['email', 'dummy'],
				'[{"name":"TestUser","email":"testuser@example.com","age":7357},{"name":"TestUser2","email":"testuser2@example.com","age":73571}]',
				'[{"name":"TestUser","email":"dummy","age":7357},{"name":"TestUser2","email":"dummy","age":73571}]',
				'Did not strip the email for the dummy in a 2-element list'
			],
			[
				['email', 'dummy'],
				'[{"name":"TestUser","email":"tu@example.com","age":7357}]',
				'[{"name":"TestUser","email":"dummy","age":7357}]',
				'Did not strip the email for the dummy in a 1-element list'
			],
			[
				['name', 'age', 'dummy'],
				'[{"name":"TestUser","email":"testuser@example.com","age":7357},{"name":"TestUser2","email":"testuser2@example.com","age":73571}]',
				'[{"name":"dummy","email":"testuser@example.com","age":"dummy"},{"name":"dummy","email":"testuser2@example.com","age":"dummy"}]',
				'Did not strip the name,age for the dummy in a 2-element list'
			],

			# Empty list
			[
				[''],
				'[]',
				'[]',
				'TODO'
			],
			[
				['email'],
				'[]',
				'[]',
				'TODO'
			],

			# Empty object
			[
				[''],
				'{}',
				'{}',
				'TODO'
			],
			[
				['email'],
				'{}',
				'{}',
				'TODO'
			],
		];
	}

	/**
	 * @dataProvider testDataProvider
	 */
	public function testFilter( $filterParams, $input, $expected, $message = '' ) {
		$input = Parser::jsonDecode( $input );
		$expected = Parser::jsonDecode( $expected );

		$output = CensorKeysFilter::doFilter( $input, $filterParams );

		$this->assertTrue( $expected === $output,
			'The CensorKeysFilter ' . lcfirst( $message ) . '.'
		);
	}
}
