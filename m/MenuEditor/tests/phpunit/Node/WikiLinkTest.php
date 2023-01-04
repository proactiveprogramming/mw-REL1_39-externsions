<?php

namespace MediaWiki\Extension\MenuEditor\Tests\Node;

use MediaWiki\Extension\MenuEditor\Node\WikiLink;

class WikiLinkTest extends TwoFoldLinkSpecTest {

	/**
	 * @param array $input
	 * @param array $mutate
	 * @param string $expected
	 * @dataProvider provideData
	 * @covers \MediaWiki\Extension\MenuEditor\Node\WikiLink::setLabel
	 * @covers \MediaWiki\Extension\MenuEditor\Node\WikiLink::setTarget
	 * @covers \MediaWiki\Extension\MenuEditor\Node\WikiLink::getCurrentData
	 */
	public function testNode( $input, $mutate, $expected ) {
		parent::testNode( $input, $mutate, $expected );
	}

	protected function provideNode( $input ) {
		$input['titleFactory'] = $this->getTitleFactoryMock();
		return new WikiLink( ...array_values( $input ) );
	}

	/**
	 * @return array[]
	 */
	public function provideData() {
		return [
			'no-mutate' => [
				'input' => [
					'level' => 1,
					'target' => 'Foo',
					'label' => 'dummy',
					'wikitext' => "* [[Foo|dummy]]"
				],
				'mutate' => null,
				'expected' => "* [[Foo|dummy]]"
			],
			'mutate' => [
				'input' => [
					'level' => 2,
					'target' => 'Foo',
					'label' => 'dummy',
					'wikitext' => "** [[Foo|dummy]]"
				],
				'mutate' => [
					'level' => 3,
					'target' => 'Bar',
					'label' => 'quick-brown-fox'
				],
				'expected' => "*** [[Bar|quick-brown-fox]]"
			],
			'no-label' => [
				'input' => [
					'level' => 2,
					'target' => 'Foo',
					'label' => '',
					'wikitext' => "** [[Foo]]"
				],
				'mutate' => [
					'target' => 'Test',
					'level' => 3
				],
				'expected' => "*** [[Test]]"
			],
			'mutate-invalid' => [
				'input' => [
					'level' => 1,
					'target' => 'Foo',
					'label' => 'dummy',
					'wikitext' => "** [[Foo|dummy]]",
				],
				'mutate' => [
					'target' => 'Invalid@title',
				],
				'expected' => "exception"
			],
		];
	}
}
