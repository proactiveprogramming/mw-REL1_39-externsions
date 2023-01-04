<?php

namespace jsoner\filter;

use jsoner\Parser;

class RemoveKeysFilterTest extends \PHPUnit_Framework_TestCase
{
	public function testDataProvider() {
		return [
			[
				['email'],
				'[{"first":{},"email":"test@example.com","value":31,"demo":true,"list":["this","that","more"]}]',
				'[{"first":{},"value":31,"demo":true,"list":["this","that","more"]}]',
				'Did not strip single element in complex 1-element list'

			],
			[
				['name'],
				'[{"name":"Jonas","email":"what@example.com"},{"name":"Tobi","email":"nope@example.com"}]',
				'[{"email":"what@example.com"},{"email":"nope@example.com"}]',
				'Did not strip single element in 2-element list'
			],
			[
				['name'],
				'[{"name":"Tobi","email":"nope@example.com"}]',
				'[{"email":"nope@example.com"}]',
				'Did not strip single element in 1-element list'
			],
			[
				['name', 'email'],
				'[{"name":"Tobi","email":"nope@example.com"}]',
				'[{}]',
				'Did not strip all elements'
			],

			# Empty list
			[
				[''],
				'[]',
				'[]'
			],
			[
				['email'],
				'[]',
				'[]'
			],

			# Empty object
			[
				[''],
				'{}',
				'{}'
			],
			[
				['email'],
				'{}',
				'{}'
			],
		];
	}

	/**
	 * @dataProvider testDataProvider
	 */
	public function testFilter( $filterParams, $input, $expected, $message = 'Had some error' ) {
		$input = Parser::jsonDecode( $input );
		$expected = Parser::jsonDecode( $expected );

		$output = RemoveKeysFilter::doFilter( $input, $filterParams );

		$this->assertTrue( $expected === $output,
			'The RemoveKeysFilter ' . lcfirst( $message ) . '.'
		);
	}
}
