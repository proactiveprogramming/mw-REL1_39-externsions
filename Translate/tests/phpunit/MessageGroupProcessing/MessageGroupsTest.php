<?php
declare( strict_types = 1 );

namespace MediaWiki\Extension\Translate\MessageGroupProcessing;

use HashBagOStuff;
use HashMessageIndex;
use MediaWikiIntegrationTestCase;
use MessageIndex;
use WANObjectCache;

/**
 * @author Niklas Laxström
 * @group Database
 * ^ See AggregateMessageGroup::getGroups -> MessageGroups::getPriority
 * @covers MediaWiki\Extension\Translate\MessageGroupProcessing\MessageGroups
 * @license GPL-2.0-or-later
 */
class MessageGroupsTest extends MediaWikiIntegrationTestCase {
	protected function setUp(): void {
		parent::setUp();

		$conf = [
			__DIR__ . '../../data/ParentGroups.yaml',
			__DIR__ . '../../data/ValidatorGroup.yaml'
		];

		$this->setMwGlobals( [
			'wgTranslateGroupFiles' => $conf,
			'wgTranslateTranslationServices' => [],
			'wgTranslateMessageNamespaces' => [ NS_MEDIAWIKI ],
		] );

		$this->setTemporaryHook( 'TranslateInitGroupLoaders',
			'FileBasedMessageGroupLoader::registerLoader' );

		$mg = MessageGroups::singleton();
		$mg->setCache( new WANObjectCache( [ 'cache' => new HashBagOStuff() ] ) );
		$mg->recache();

		$hashIndex = new HashMessageIndex();
		MessageIndex::setInstance( $hashIndex );
		$hashIndex->rebuild();
	}

	/** @dataProvider provideGroups */
	public function testGetParentGroups( $expected, $target ) {
		$group = MessageGroups::getGroup( $target );
		$got = MessageGroups::getParentGroups( $group );
		$this->assertEquals( $expected, $got );
	}

	public static function provideGroups(): array {
		$cases = [];
		$cases[] = [
			[ [ 'root1' ], [ 'root2' ] ],
			'twoparents'
		];

		$cases[] = [
			[ [ 'root3', 'sub1' ], [ 'root3', 'sub2' ] ],
			'oneparent-twopaths'
		];

		$cases[] = [
			[
				[ 'root4' ],
				[ 'root4', 'nested1' ],
				[ 'root4', 'nested1', 'nested2' ],
				[ 'root4', 'nested2' ],
			],
			'multilevelnested'
		];

		return $cases;
	}

	public function testHaveSingleSourceLanguage(): void {
		$this->setMwGlobals( [
			'wgTranslateGroupFiles' => [ __DIR__ . '../../data/MixedSourceLanguageGroups.yaml' ],
		] );
		MessageGroups::singleton()->recache();

		$enGroup1 = MessageGroups::getGroup( 'EnglishGroup1' );
		$enGroup2 = MessageGroups::getGroup( 'EnglishGroup2' );
		$teGroup1 = MessageGroups::getGroup( 'TeluguGroup1' );

		$this->assertEquals( 'en', MessageGroups::haveSingleSourceLanguage(
			[ $enGroup1, $enGroup2 ] )
		);
		$this->assertSame( '', MessageGroups::haveSingleSourceLanguage(
			[ $enGroup1, $enGroup2, $teGroup1 ] )
		);
	}

	public function testGroupYAMLParsing(): void {
		$group = MessageGroups::getGroup( 'test-validator-group' );
		$msgValidator = $group->getValidator();
		$suggester = $group->getInsertablesSuggester();

		$this->assertCount( 1, $msgValidator->getValidators() );
		$this->assertCount( 2, $suggester->getInsertables( "$1 \case" ) );
	}
}
