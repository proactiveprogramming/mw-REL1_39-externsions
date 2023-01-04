<?php
/**
 * Copyright (C) 2016, 2021 Kunal Mehta <legoktm@debian.org>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

use MediaWiki\VersionInfo\Version;

/**
 * @covers \MediaWiki\VersionInfo\Version
 */
class VersionTest extends MediaWikiIntegrationTestCase {

	protected function assertVersion( array $expected, Version $version ) {
		$this->assertEquals( $expected[0], $version->major );
		$this->assertEquals( $expected[1], $version->minor );
		$this->assertEquals( $expected[2], $version->patch );
	}

	/**
	 * @dataProvider provideNewFromString
	 */
	public function testNewFromString( $input, $expected ) {
		$version = Version::newFromString( $input );
		if ( $expected === null ) {
			$this->assertEquals( $expected, $version );
		} else {
			$this->assertInstanceOf( Version::class, $version );
			$this->assertVersion( $expected, $version );
		}
	}

	public static function provideNewFromString() {
		return [
			[
				'1.27.1',
				[ 1, 27, 1 ]
			],
			[
				'1.26',
				[ 1, 26, 'x' ]
			]
		];
	}

	/**
	 * @dataProvider provideToString
	 */
	public function testToString( $input, $expected ) {
		$version = new Version( $input[0], $input[1], $input[2] );
		$this->assertEquals( $expected, (string)$version );
	}

	public static function provideToString() {
		return [
			[
				[ 1, 27, 1 ],
				'1.27.01'
			],
			[
				[ 1, 27, 11 ],
				'1.27.11'
			],
			[
				[ 1, 9, 1 ],
				'1.09.01'
			],
			[
				[ 1, 26, 'x' ],
				'1.26'
			]
		];
	}
}
