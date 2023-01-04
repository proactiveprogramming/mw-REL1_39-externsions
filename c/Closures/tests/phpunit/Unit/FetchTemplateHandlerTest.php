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

use Closures\ClosureStore;
use Closures\FetchTemplateHandler;
use MediaWikiUnitTestCase;
use Parser;
use Title;

/**
 * Class FetchTemplateHandlerTest
 *
 * @covers \Closures\FetchTemplateHandler
 * @group closures-unit
 *
 * @since 1.0.0
 * @package Closures\Test\Unit
 * @author Marijn van Wezel
 * @license GPL-2.0-or-later
 */
class FetchTemplateHandlerTest extends MediaWikiUnitTestCase {
	public function testCanConstruct() {
		$closure_store_mock = $this->getMockBuilder( ClosureStore::class )->getMock();
		$parser_mock = $this->getMockBuilder( Parser::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			FetchTemplateHandler::class,
			new FetchTemplateHandler( $parser_mock, $closure_store_mock )
		);
	}

	/**
	 * This test case covers a call to "handleFetchTemplate" with a Title
	 * object that is in the Template namespace for which a closure exists
	 * in the closure store.
	 *
	 * An example would be the title for "Template:Foobar" with "Foobar" in
	 * the ClosureStore. For titles in the Template namespace, the namespace
	 * prefix should not be taken into account.
	 */
	public function testHandleFetchTemplateClosureTemplateNamespace() {
		$closure_store_mock = $this->getMockBuilder( ClosureStore::class )->getMock();
		$closure_store_mock->method( "exists" )
			->with( $this->equalTo( "ExampleClosure" ) )
			->willReturn( true );
		$closure_store_mock->method( "get" )
			->with( $this->equalTo( "ExampleClosure" ) )
			->willReturn( "Testing is a virtue" );

		$title_mock = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();
		$title_mock->method( "getNamespace" )->willReturn( NS_TEMPLATE );
		$title_mock->method( "getText" )->willReturn( "ExampleClosure" );

		$parser_mock = $this->getMockBuilder( Parser::class )
			->disableOriginalConstructor()
			->getMock();

		$fetch_template_handler = new FetchTemplateHandler( $parser_mock, $closure_store_mock );

		$template_array = $fetch_template_handler->handleFetchTemplate( $title_mock );

		$this->assertArrayEquals( [ "text" => "Testing is a virtue" ], $template_array );
	}

	/**
	 * This test case covers a call to "handleFetchTemplate" with a Title
	 * object that is in the User namespace for which a closure exists
	 * in the closure store.
	 *
	 * An example would be the title for "User:Foobar" with "User:Foobar" in
	 * the ClosureStore.
	 */
	public function testHandleFetchTemplateClosureUserNamespace() {
		$closure_store_mock = $this->getMockBuilder( ClosureStore::class )->getMock();
		$closure_store_mock->method( "exists" )
			->with( $this->equalTo( "User:ExampleClosure" ) )
			->willReturn( true );
		$closure_store_mock->method( "get" )
			->with( $this->equalTo( "User:ExampleClosure" ) )
			->willReturn( "Testing is a virtue" );

		$title_mock = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();
		$title_mock->method( "getNamespace" )->willReturn( NS_USER );
		$title_mock->method( "getFullText" )->willReturn( "User:ExampleClosure" );

		$parser_mock = $this->getMockBuilder( Parser::class )
			->disableOriginalConstructor()
			->getMock();

		$fetch_template_handler = new FetchTemplateHandler( $parser_mock, $closure_store_mock );

		$template_array = $fetch_template_handler->handleFetchTemplate( $title_mock );

		$this->assertArrayEquals( [ "text" => "Testing is a virtue" ], $template_array );
	}
}
