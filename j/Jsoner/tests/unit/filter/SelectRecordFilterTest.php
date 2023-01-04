<?php
/**
 * Created by IntelliJ IDEA.
 * User: anfink
 * Date: 15/12/16
 * Time: 13:35
 */

namespace jsoner\filter;

use jsoner\Parser;

class SelectRecordFilterTest extends \PHPUnit_Framework_TestCase
{
	public function testDataProvider() {
		return [
			[
				'email:test2@example.com',
				'[
					{"first":{},"email":"test1@example.com","value":31,"demo":true,"list":["this","that","more"]},
					{"first":{},"email":"test2@example.com","value":32,"demo":false,"list":["this","that","more"]},
					{"first":{},"email":"test3@example.com","value":33,"demo":false,"list":["this","that","more"]}
				]',
				'[{"first":{},"email":"test2@example.com","value":32,"demo":false,"list":["this","that","more"]}]'
			]
		];
	}

	/**
	 * @dataProvider testDataProvider
	 */
	public function testFilter($filterParams, $input, $expected, $message = '') {
		$input = Parser::jsonDecode($input);
		$expected = Parser::jsonDecode($expected);

		$output = SelectRecordFilter::doFilter($input, $filterParams);

		$this->assertEquals(1, count($output));
		$this->assertTrue($expected === $output);
	}
}
