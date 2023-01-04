<?php

namespace jsoner\filter;

use jsoner\Parser;

class SelectKeysFilterTest extends \PHPUnit_Framework_TestCase
{
	public function testDataProvider() {
		return [
			[
				['email'],
				'[{"first":{},"email":"test@example.com","value":31,"demo":true,"list":["this","that","more"]}]',
				'[{"email":"test@example.com"}]'
			],
			[
				['email'],
				'[{"name":"Jonas","email":"what@example.com"},{"name":"Tobi","email":"nope@example.com"}]',
				'[{"email":"what@example.com"},{"email":"nope@example.com"}]'
			],
			[
				['name'],
				'[{"name":"Jonas","email":"what@example.com"},{"name":"Tobi","email":"nope@example.com"}]',
				'[{"name":"Jonas"},{"name":"Tobi"}]'
			],

			# Empty list
			[
				[''],
				'[]',
				'[]'],
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
	public function testFilter( $filterParams, $input, $expected, $message = '' ) {
		$input = Parser::jsonDecode( $input );
		$expected = Parser::jsonDecode( $expected );

		$output = SelectKeysFilter::doFilter( $input, $filterParams );

		$this->assertTrue( $expected === $output,
			'The SelectKeysFilter ' . lcfirst( $message ) . '.'
		);
	}
}
