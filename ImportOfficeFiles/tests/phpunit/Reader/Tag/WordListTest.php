<?php

namespace MediaWiki\Extension\ImportOfficeFiles\Tests\Reader\Tag;

use MediaWiki\Extension\ImportOfficeFiles\Reader\Component\Word2007DocumentData;
use MediaWiki\Extension\ImportOfficeFiles\Tests\Reader\DummyDocumentData;

/**
 * @covers \MediaWiki\Extension\ImportOfficeFiles\Reader\Tag\Instr
 */
class WordListTest extends TagProcessorTestBase {

	/**
	 * @return string
	 */
	protected function getProcessorName(): string {
		return 'paragraph';
	}

	/**
	 * @return Word2007DocumentData
	 */
	protected function getDocumentData(): Word2007DocumentData {
		return new DummyDocumentData( [
			'numbering' => [
				'listDefinitions' => [
					1 => [
						0 => [
							'ordered' => true
						],
						1 => [
							'ordered' => true
						]
					],
					2 => [
						0 => [
							'ordered' => false
						],
						1 => [
							'ordered' => false
						]
					]
				],
				'listMapping' => [
					1 => 1,
					2 => 2
				]
			]
		] );
	}

	/**
	 * @return string
	 */
	protected function getTestInputFileName(): string {
		return 'wordlist.input.xml';
	}

	/**
	 * @return string
	 */
	protected function getTestResultFileName(): string {
		return 'wordlist.result.xml';
	}
}
