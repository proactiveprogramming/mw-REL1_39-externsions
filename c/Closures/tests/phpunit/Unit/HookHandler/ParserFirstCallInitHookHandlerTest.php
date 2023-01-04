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
use Closures\HookHandler\ParserFirstCallInitHookHandler;
use MediaWikiUnitTestCase;
use Parser;

/**
 * Class ParserFirstCallInitHookHandlerTest
 *
 * @covers \Closures\HookHandler\ParserFirstCallInitHookHandler
 * @group closures-unit
 *
 * @since 1.0.0
 * @package Closures\Test\Unit
 * @author Marijn van Wezel
 * @license GPL-2.0-or-later
 */
class ParserFirstCallInitHookHandlerTest extends MediaWikiUnitTestCase {
	public function testCanConstruct() {
		$closure_store_mock = $this->getMockBuilder( ClosureStore::class )->getMock();

		$this->assertInstanceOf(
			ParserFirstCallInitHookHandler::class,
			new ParserFirstCallInitHookHandler( $closure_store_mock )
		);
	}

	public function testOnParserFirstCallInitSetsFunctionHook() {
		$closure_store_mock = $this->getMockBuilder( ClosureStore::class )->getMock();
		$parser_first_call_init_hook_handler = new ParserFirstCallInitHookHandler(
			$closure_store_mock
		);

		$parser_mock = $this->getMockBuilder( Parser::class )
			->disableOriginalConstructor()
			->getMock();

		$parser_mock->expects( $this->once() )->method( "setFunctionHook" );

		$parser_first_call_init_hook_handler->onParserFirstCallInit( $parser_mock );
	}
}
