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

use BadTitleError;
use Closures\ClosureStore;
use MediaWikiIntegrationTestCase;

/**
 * Class ClosureStoreTest
 *
 * @covers \Closures\ClosureStore
 * @group closures-integration
 *
 * @since 1.0.0
 * @package Closures\Tests\Integration
 * @author Marijn van Wezel
 * @license GPL-2.0-or-later
 */
class ClosureStoreTest extends MediaWikiIntegrationTestCase {
	/**
	 * @var ClosureStore The closure store under test
	 */
	private ClosureStore $closure_store;

	public function setUp(): void {
		$this->closure_store = new ClosureStore();
		parent::setUp();
	}

	/**
	 * @dataProvider validClosureNames
	 * @param string $closure_name A valid closure name
	 * @throws BadTitleError
	 */
	public function testRegisterValidClosureName( string $closure_name ) {
		$this->closure_store->add( $closure_name, "" );
		$this->assertTrue( $this->closure_store->exists( $closure_name ) );
	}

	/**
	 * @dataProvider invalidClosureNames
	 * @param string $closure_name An invalid closure name
	 * @throws BadTitleError
	 */
	public function testRegisterInvalidClosureName( string $closure_name ) {
		$this->expectException( BadTitleError::class );
		$this->closure_store->add( $closure_name, "" );
	}

	/**
	 * @dataProvider validClosureNames
	 * @param string $closure_name A valid closure name
	 * @throws \BadTitleError
	 */
	public function testCaseIsIgnoredInClosureName( string $closure_name ) {
		$this->closure_store->add( $closure_name, "Testing is a virtue" );

		$closure_name_mutations = [
			ucfirst( $closure_name ),
			lcfirst( $closure_name ),
			strtoupper( $closure_name ),
			strtolower( $closure_name )
		];

		foreach ( $closure_name_mutations as $mutation ) {
			$this->assertTrue( $this->closure_store->exists( $mutation ) );
			$this->assertSame( "Testing is a virtue", $this->closure_store->get( $mutation ) );
		}
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
}
