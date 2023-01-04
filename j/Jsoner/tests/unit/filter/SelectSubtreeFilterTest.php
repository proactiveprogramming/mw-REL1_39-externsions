<?php
namespace jsoner\filter;

use jsoner\Parser;

class SelectSubtreeFilterTest extends \PHPUnit_Framework_TestCase
{
	public function testDataProvider() {
		return [
			[
				'menu',
				'{"menu":{"id":"file","value":"File","popup":{"menuitem":[{"value":"New","onclick":"CreateNewDoc()"},{"value":"Open","onclick":"OpenDoc()"},{"value":"Close","onclick":"CloseDoc()"}]}}}',
				'{"id":"file","value":"File","popup":{"menuitem":[{"value":"New","onclick":"CreateNewDoc()"},{"value":"Open","onclick":"OpenDoc()"},{"value":"Close","onclick":"CloseDoc()"}]}}'
			],
			[
				'menuitem',
				'{"menuitem":[{"value":"New","onclick":"CreateNewDoc()"},{"value":"Open","onclick":"OpenDoc()"},{"value":"Close","onclick":"CloseDoc()"}]}',
				'[{"value":"New","onclick":"CreateNewDoc()"},{"value":"Open","onclick":"OpenDoc()"},{"value":"Close","onclick":"CloseDoc()"}]'
			],
			[
				'small',
				'{"small":{"0":"a","1":"b"}}',
				'{"0":"a","1":"b"}'
			],

			# If the data from the parser contains a list of objects, the filter should do nothing
			[
				'0',
				'[{"key": "value"},{"key": "value"}]',
				'[{"key": "value"},{"key": "value"}]'
			],

			# Empty data
			[
				'',
				'[]',
				'[]'
			],
			[
				'',
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

		$output = SelectSubtreeFilter::doFilter( $input, $filterParams );

		$this->assertTrue( $expected === $output,
			'The SelectSubtreeFilter ' . lcfirst( $message ) . '.'
		);
	}
}
