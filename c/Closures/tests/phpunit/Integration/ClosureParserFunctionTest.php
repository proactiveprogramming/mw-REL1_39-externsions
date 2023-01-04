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

namespace Closures\Tests\Integration;

use Closures\ClosureParserFunction;
use Closures\ClosureStore;
use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use Parser;
use PPFrame;

/**
 * Class ClosureParserFunctionTest
 *
 * @covers \Closures\ClosureParserFunction
 * @group closures-integration
 *
 * @since 1.0.0
 * @package Closures\Test\Integration
 * @author Marijn van Wezel
 * @license GPL-2.0-or-later
 */
class ClosureParserFunctionTest extends MediaWikiIntegrationTestCase {
	public function testHandleFunctionHookNoClosureName() {
		$closure_store_mock = $this->getMockBuilder( ClosureStore::class )->getMock();

		$parser_mock = $this->getMockBuilder( Parser::class )
			->disableOriginalConstructor()
			->getMock();

		$frame_mock = $this->getMockBuilder( PPFrame::class )
			->disableOriginalConstructor()
			->getMock();

		$closure_parser_function = new ClosureParserFunction( $closure_store_mock );

		$this->assertSame(
			wfmessage( "closures-missing-closure-name" )->parse(),
			$closure_parser_function->handleFunctionHook( $parser_mock, $frame_mock, [] )
		);
	}

	/**
	 * @dataProvider invalidClosureNames
	 * @param string $closure_name An invalid closure name
	 */
	public function testHandleFunctionHookInvalidClosureName( string $closure_name ) {
		$parser_mock = $this->getMockBuilder( Parser::class )
			->disableOriginalConstructor()
			->getMock();

		$frame_mock = $this->getMockBuilder( PPFrame::class )
			->disableOriginalConstructor()
			->getMock();
		$frame_mock->method( "expand" )->willReturn( $closure_name );

		$closure_store = new ClosureStore();

		$closure_parser_function = new ClosureParserFunction( $closure_store );

		$this->assertSame(
			wfmessage( "closures-bad-closure-name" )->parse(),
			$closure_parser_function->handleFunctionHook( $parser_mock, $frame_mock, [ $closure_name ] )
		);

		$this->assertFalse( $closure_store->exists( $closure_name ) );
	}

	/**
	 * @dataProvider functionHookBodies
	 * @param string $expected The expected body for the given args
	 * @param array $args The given args, excluding the closure name
	 */
	public function testHandleFunctionHookPipesAreConcatenated( string $expected, array $args ) {
		$closure_store_mock = $this->getMockBuilder( ClosureStore::class )->getMock();
		$closure_store_mock->expects( $this->once() )
			->method( "add" )
			->with( "Foobar", $expected );

		$parser = MediaWikiServices::getInstance()->getParser();
		$user = \RequestContext::getMain()->getUser();

		$parser->setOptions( \ParserOptions::newCanonical( $user ) );
		$frame = new \PPFrame_Hash( $parser->getPreprocessor() );

		$closure_parser_function = new ClosureParserFunction( $closure_store_mock );

		$this->assertSame(
			"",
			$closure_parser_function->handleFunctionHook(
				$parser, $frame, array_merge( [ "Foobar" ], $args )
			)
		);
	}

	/**
	 * Data provider that provides invalid closure names.
	 *
	 * @return string[][]
	 */
	public function invalidClosureNames(): array {
		return [
			[ "|" ],
			[ "<" ],
			[ ">" ],
			[ "[" ],
			[ "]" ],
			[ "{" ],
			[ "}" ],
			[ "TemplateName |" ],
			[ "TemplateName <>", ],
			[ "Template Name ! {} [] <> |" ],
			[ "Template|Name" ],
			[ "Template:|:Name" ],
			[ "|:Template" ]
		];
	}

	/**
	 * Data provider that provides a function body and the
	 * arguments used to create the function body.
	 *
	 * @return array[]
	 */
	public function functionHookBodies(): array {
		return [
			[ "{|-\n|-\n |} ", [ "{", "-\n", "-\n ", "} " ] ],
			[ "\n", [ "\n" ] ],
			[ "{{Foo|Bar}}", [ "{{Foo", "Bar}}" ] ],
			[ " ", [ " " ] ],
			[ "", [ "" ] ],
			[ "", [] ],
			[ "|", [ "", "" ] ],
			[ "{{||}}", [ "{{", "", "}}" ] ],
			[ "{{| | |}}|", [ "{{", " ", " ", "}}", "" ] ],
			[ "||||", [ "", "", "", "", "" ] ]
		];
	}
}
