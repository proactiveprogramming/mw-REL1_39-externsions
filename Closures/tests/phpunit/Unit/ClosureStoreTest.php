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
use MediaWikiUnitTestCase;
use OutOfBoundsException;

/**
 * Class ClosureStoreTest
 *
 * @covers \Closures\ClosureStore
 * @group closures-unit
 *
 * @since 1.0.0
 * @package Closures\Test\Unit
 * @author Marijn van Wezel
 * @license GPL-2.0-or-later
 */
class ClosureStoreTest extends MediaWikiUnitTestCase {
	/**
	 * @var ClosureStore The closure store under test
	 */
	private ClosureStore $closure_store;

	public function setUp(): void {
		$this->closure_store = new ClosureStore();
		parent::setUp();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf( ClosureStore::class, new ClosureStore() );
	}

	/**
	 * @dataProvider validClosureNames
	 * @param string $closure_name A valid closure name
	 */
	public function testGetExistingClosure( string $closure_name ) {
		$this->closure_store->closures[strtolower( $closure_name )] = "Testing is a virtue";
		$this->assertSame( "Testing is a virtue", $this->closure_store->get( $closure_name ) );
	}

	/**
	 * @dataProvider validClosureNames
	 * @param string $closure_name A valid closure name
	 */
	public function testGetNonexistingClosure( string $closure_name ) {
		$this->expectException( OutOfBoundsException::class );
		$this->closure_store->get( $closure_name );
	}

	/**
	 * @dataProvider validClosureNames
	 * @param string $closure_name A valid closure name
	 */
	public function testExistingClosureExists( string $closure_name ) {
		$this->closure_store->closures[strtolower( $closure_name )] = "";
		$this->assertTrue( $this->closure_store->exists( $closure_name ) );
	}

	/**
	 * @dataProvider validClosureNames
	 * @param string $closure_name A valid closure name
	 */
	public function testNonexistingClosureDoesNotExist( string $closure_name ) {
		$this->assertFalse( $this->closure_store->exists( $closure_name ) );
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
