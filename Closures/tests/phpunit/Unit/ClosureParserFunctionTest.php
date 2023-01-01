<?php

/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 */

namespace Closures\Tests\Unit;

use Closures\ClosureParserFunction;
use Closures\ClosureStore;
use MediaWikiUnitTestCase;
use Parser;
use PPFrame;

/**
 * Class ClosureParserFunctionTest
 *
 * @covers \Closures\ClosureParserFunction
 * @group closures-unit
 *
 * @since 1.0.0
 * @package Closures\Test\Unit
 * @author Marijn van Wezel
 * @license GPL-2.0-or-later
 */
class ClosureParserFunctionTest extends MediaWikiUnitTestCase {
	public function testCanConstruct() {
		$closure_store_mock = $this->getMockBuilder( ClosureStore::class )->getMock();

		$this->assertInstanceOf(
			ClosureParserFunction::class,
			new ClosureParserFunction( $closure_store_mock )
		);
	}

	/**
	 * @dataProvider validClosureNames
	 * @param string $closure_name A valid closure name
	 */
	public function testHandleFunctionHookEmptyClosureBody( string $closure_name ) {
		$closure_body = "";

		$closure_store_mock = $this->getMockBuilder( ClosureStore::class )->getMock();
		$closure_store_mock->expects( $this->once() )
			->method( "add" )
			->with( $closure_name, $closure_body );

		$parser_mock = $this->getMockBuilder( Parser::class )
			->disableOriginalConstructor()
			->getMock();

		$frame_mock = $this->getMockBuilder( PPFrame::class )
			->disableOriginalConstructor()
			->getMock();

		$frame_mock->expects( $this->at( 0 ) )->method( "expand" )
			->with( $closure_name )
			->will( $this->returnValue( $closure_name ) );
		$frame_mock->expects( $this->at( 1 ) )->method( "expand" )
			->with( $closure_body, PPFrame::RECOVER_ORIG )
			->will( $this->returnValue( $closure_body ) );

		$closure_parser_function = new ClosureParserFunction( $closure_store_mock );
		$this->assertSame(
			"",
			$closure_parser_function->handleFunctionHook(
				$parser_mock, $frame_mock, [ $closure_name, $closure_body ]
			)
		);
	}

	/**
	 * Data provider that provides valid closure names.
	 *
	 * @return string[][]
	 */
	public function validClosureNames(): array {
		return [
			[ "TemplateName" ],
			[ "Template Name" ],
			[ "Template name" ],
			[ "template name" ],
			[ "template name 123" ],
			[ "Template Name 123" ],
			[ "TemplateName123" ],
			[ "TemplateNAME!" ],
			[ "TEMPLATE" ],
			[ "PAGENAME" ],
			[ "TemplateName Is Valid 123 !!!" ]
		];
	}
}
