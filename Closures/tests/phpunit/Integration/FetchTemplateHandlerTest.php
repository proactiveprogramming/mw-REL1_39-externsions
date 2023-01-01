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

use Closures\ClosureStore;
use Closures\FetchTemplateHandler;
use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use Title;

/**
 * Class FetchTemplateHandlerTest
 *
 * @covers \Closures\FetchTemplateHandler
 * @group closures-integration
 *
 * @since 1.0.0
 * @package Closures\Test\Integration
 * @author Marijn van Wezel
 * @license GPL-2.0-or-later
 */
class FetchTemplateHandlerTest extends MediaWikiIntegrationTestCase {
	/**
	 * This test case covers a call to "handleFetchTemplate" with a Title
	 * object that is in the Template namespace for which no closure exists.
	 *
	 * An example would be the title for "Template:Foobar" nothing in the
	 * ClosureStore.
	 */
	public function testTemplateTransclusionsBehaveIdenticallyWhenNotClosure() {
		$closure_store_mock = $this->getMockBuilder( ClosureStore::class )->getMock();
		$closure_store_mock->method( "exists" )->willReturn( false );

		$title = Title::makeTitleSafe( NS_TEMPLATE, "ExampleClosure" );
		$parser = MediaWikiServices::getInstance()->getParser();

		$fetch_template_handler = new FetchTemplateHandler( $parser, $closure_store_mock );
		$template_array = $fetch_template_handler->handleFetchTemplate( $title );

		$this->assertEquals( $parser->statelessFetchTemplate( $title ), $template_array );
	}

	/**
	 * This test case covers a call to "handleFetchTemplate" with a Title
	 * object that is in the User namespace for which no closure exists.
	 *
	 * An example would be the title for "User:Foobar" nothing in the
	 * ClosureStore.
	 */
	public function testNamespaceTransclusionsBehaveIdenticallyWhenNotClosure() {
		$closure_store_mock = $this->getMockBuilder( ClosureStore::class )->getMock();
		$closure_store_mock->method( "exists" )->willReturn( false );

		$title = Title::makeTitleSafe( NS_USER, "ExampleClosure" );
		$parser = MediaWikiServices::getInstance()->getParser();

		$fetch_template_handler = new FetchTemplateHandler( $parser, $closure_store_mock );
		$template_array = $fetch_template_handler->handleFetchTemplate( $title );

		$this->assertEquals( $parser->statelessFetchTemplate( $title ), $template_array );
	}
}
