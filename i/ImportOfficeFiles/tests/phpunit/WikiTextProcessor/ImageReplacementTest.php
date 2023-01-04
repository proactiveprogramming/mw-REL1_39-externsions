<?php

namespace MediaWiki\Extension\ImportOfficeFiles\Tests\WikiTextProcessor;

use MediaWiki\Extension\ImportOfficeFiles\Modules\MSOfficeWord;
use MediaWiki\Extension\ImportOfficeFiles\WikiTextProcessor\ImageReplacement;
use MediaWiki\Extension\ImportOfficeFiles\Workspace;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MediaWiki\Extension\ImportOfficeFiles\WikiTextProcessor\ImageReplacement
 */
class ImageReplacementTest extends TestCase {

	/**
	 * @covers \MediaWiki\Extension\ImportOfficeFiles\WikiTextProcessor\ImageReplacement::process()
	 * @return void
	 */
	public function testProcess() {
		$workspaceMock = $this->createMock( Workspace::class );
		$workspaceMock->method( 'loadBucket' )->willReturnCallback( static function ( $name ) {
			if ( $name === MSOfficeWord::BUCKET_ANALYZER ) {
				return [
					'base-title' => 'Some base title',
					'namespace'  => 'TestCase',
				];
			}
			if ( $name === MSOfficeWord::BUCKET_MEDIA_ID_FILENAME ) {
				return [
					'rId10' => 'filename1.jpg',
					'rId11' => 'filename2.png'
				];
			}

			return [];
		} );

		$wikitext = 'Hello ###PRESERVEIMAGE {"id":"rId10","inline":true}###';
		$wikitext .= ' world ';
		$wikitext .= '###PRESERVEIMAGE {"width":"100", "height":"200","id":"rId11","anchor":true}### !';
		$wikitext .= ' ###PRESERVEIMAGE {"width":"100", "height":"30","id":"rId11","inline":true}### !';

		$imageReplacement = new ImageReplacement( $workspaceMock );
		$actualWikitext = $imageReplacement->process( $wikitext );

		$expectedWikitext = 'Hello [[File:TestCase_Some_base_title_filename1.jpg|border]]';
		$expectedWikitext .= ' world ';
		$expectedWikitext .= '[[File:TestCase_Some_base_title_filename2.png|thumb|center|100x200px]] !';
		$expectedWikitext .= ' [[File:TestCase_Some_base_title_filename2.png|border|100x30px]] !';

		$this->assertEquals( $expectedWikitext, $actualWikitext );
	}

}
