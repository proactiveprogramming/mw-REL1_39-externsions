<?php

use MediaWiki\TimedMediaHandler\Handlers\OggHandler\OggHandler;
use MediaWiki\TimedMediaHandler\Handlers\WebMHandler\WebMHandler;

/**
 * @covers \MediaWiki\TimedMediaHandler\Handlers\WebMHandler\WebMHandler
 */
class WebMHandlerTest extends MediaWikiMediaTestCase {

	/** @var OggHandler */
	private $handler;

	public function getFilePath() {
		return __DIR__ . '/media';
	}

	protected function setUp(): void {
		parent::setUp();
		$this->handler = new WebMHandler;
	}

	/**
	 * @dataProvider providerGetStreamTypes
	 * @param string $filename name of file
	 * @param array $expected List of codecs in file
	 */
	public function testGetStreamTypes( $filename, $expected ) {
		$testFile = $this->dataFile( $filename, 'video/webm' );
		$this->assertEquals( $expected, $this->handler->getStreamTypes( $testFile ) );
	}

	public function providerGetStreamTypes() {
		return [
			[ 'shuttle10seconds.1080x608.webm', [ 'VP8' ] ],
			[ 'VP9-tractor.webm', [ 'VP9' ] ],
			[ 'bear-vp9-opus.webm', [ 'VP9', 'Opus' ] ]
		];
	}

	/**
	 * @dataProvider providerGetWebType
	 * @param string $filename name of file
	 * @param string $expected Mime type
	 */
	public function testGetWebType( $filename, $expected ) {
		$testFile = $this->dataFile( $filename, 'video/webm' );
		$this->assertEquals( $expected, $this->handler->getWebType( $testFile ) );
	}

	public function providerGetWebType() {
		return [
			[ 'shuttle10seconds.1080x608.webm', 'video/webm; codecs="vp8"' ],
			[ 'VP9-tractor.webm', 'video/webm; codecs="vp9"' ],
			[ 'bear-vp9-opus.webm', 'video/webm; codecs="vp9, opus"' ]
		];
	}
}
