<?php

namespace MediaWiki\Extension\ImportOfficeFiles\Tests\Reader;

use MediaWiki\Extension\ImportOfficeFiles\Reader\Component\Word2007DocumentData;
use MediaWiki\Extension\ImportOfficeFiles\Reader\Component\Word2007Numbering;

/**
 * Class for setting dummy document data.
 * Only fo testing purposes!
 */
class DummyDocumentData extends Word2007DocumentData {

	/**
	 * @param array $dummyDocumentData
	 */
	public function __construct( array $dummyDocumentData ) {
		if ( isset( $dummyDocumentData['rels'] ) ) {
			$this->rels = $dummyDocumentData['rels'];
		}

		if ( isset( $dummyDocumentData['styles'] ) ) {
			$this->styles = $dummyDocumentData['styles'];
		}

		if ( isset( $dummyDocumentData['numbering'] ) ) {

			$listDefinitions = [];
			if ( isset( $dummyDocumentData['numbering']['listDefinitions'] ) ) {
				$listDefinitions = $dummyDocumentData['numbering']['listDefinitions'];
			}

			$listMapping = [];
			if ( isset( $dummyDocumentData['numbering']['listMapping'] ) ) {
				$listMapping = $dummyDocumentData['numbering']['listMapping'];
			}

			$this->numbering = new Word2007Numbering( $listDefinitions, $listMapping );
		}
	}
}
