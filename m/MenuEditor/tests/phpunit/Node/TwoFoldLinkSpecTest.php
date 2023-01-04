<?php

namespace MediaWiki\Extension\MenuEditor\Tests\Node;

use MediaWiki\Extension\MenuEditor\Node\TwoFoldLinkSpec;

class TwoFoldLinkSpecTest extends MenuNodeTest {

	/**
	 * @param array $input
	 * @param array $mutate
	 * @param string $expected
	 * @dataProvider provideData
	 * @covers \MediaWiki\Extension\MenuEditor\Node\TwoFoldLinkSpec::setLabel
	 * @covers \MediaWiki\Extension\MenuEditor\Node\TwoFoldLinkSpec::setTarget
	 * @covers \MediaWiki\Extension\MenuEditor\Node\TwoFoldLinkSpec::getCurrentData
	 */
	public function testNode( $input, $mutate, $expected ) {
		parent::testNode( $input, $mutate, $expected );
	}

	protected function provideNode( $input ) {
		$input['titleFactory'] = $this->getTitleFactoryMock();
		return new TwoFoldLinkSpec( ...array_values( $input ) );
	}

	protected function getTitleFactoryMock() {
		$titleFactoryMock = $this->createMock( \TitleFactory::class );
		$titleFactoryMock->method( 'newFromText' )->willReturnCallback( function ( $name ) {
			if ( strpos( $name, '@' ) ) {
				return null;
			}
			return $this->createMock( \Title::class );
		} );

		return $titleFactoryMock;
	}

	/**
	 * @return array[]
	 */
	public function provideData() {
		return [
			'no-mutate' => [
				'input' => [
					'target' => 'Foo',
					'label' => 'dummy',
					'wikitext' => "** Foo|dummy"
				],
				'mutate' => null,
				'expected' => "** Foo|dummy"
			],
			'mutate-target' => [
				'input' => [
					'target' => 'Foo',
					'label' => 'dummy',
					'wikitext' => "** Foo|dummy"
				],
				'mutate' => [
					'target' => 'https://test.com',
					// Setting level should have no effect
					'level' => 3,
					'label' => 'quick-brown-fox'
				],
				'expected' => "** https://test.com|quick-brown-fox"
			],
			'mutate-invalid' => [
				'input' => [
					'target' => 'Foo',
					'label' => 'dummy',
					'wikitext' => "** Foo|dummy"
				],
				'mutate' => [
					'target' => 'Invalid@title',
				],
				'expected' => "exception"
			],
		];
	}
}
