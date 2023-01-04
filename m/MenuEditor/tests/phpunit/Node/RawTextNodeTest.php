<?php

namespace MediaWiki\Extension\MenuEditor\Tests\Node;

use MediaWiki\Extension\MenuEditor\Node\RawText;

class RawTextNodeTest extends MenuNodeTest {

	/**
	 * @param array $input
	 * @param array $mutate
	 * @param string $expected
	 * @dataProvider provideData
	 * @covers \MediaWiki\Extension\MenuEditor\Node\RawText::getCurrentData
	 * @covers \MediaWiki\Extension\MenuEditor\Node\RawText::setLevel
	 * @covers \MediaWiki\Extension\MenuEditor\Node\RawText::setNodeText
	 */
	public function testNode( $input, $mutate, $expected ) {
		parent::testNode( $input, $mutate, $expected );
	}

	/**
	 * @param array $input
	 * @return RawText
	 */
	protected function provideNode( $input ) {
		return new RawText( ...array_values( $input ) );
	}

	/**
	 * @return array[]
	 */
	public function provideData() {
		return [
			'no-mutate' => [
				'input' => [
					'level' => 1,
					'text' => 'Foo',
					'wikitext' => "* Foo"
				],
				'mutate' => null,
				'expected' => "* Foo"
			],
			'mutate-level' => [
				'input' => [
					'level' => 2,
					'text' => 'Foo',
					'wikitext' => "** Foo"
				],
				'mutate' => [
					'level' => 3
				],
				'expected' => "*** Foo"
			],
			'mutate-level-and-text' => [
				'input' => [
					'level' => 1,
					'text' => 'Foo',
					'wikitext' => "* Foo"
				],
				'mutate' => [
					'level' => 2,
					'text' => 'Bar'
				],
				'expected' => "** Bar"
			],
		];
	}
}
