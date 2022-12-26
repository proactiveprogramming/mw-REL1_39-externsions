<?php

namespace BlueSpice\ProDistributionConnector\Tests\HookHandler;

use BlueSpice\ProDistributionConnector\HookHandler\PdfExportConvertMathSVGtoPNG;
use BlueSpice\ProDistributionConnector\Math\SVGProvider;
use BsPDFServlet;
use DOMElement;
use PHPUnit\Framework\TestCase;
use SvgHandler;

class PdfExportConvertMathSVGtoPNGTest extends TestCase {

	/**
	 * @covers BlueSpice\ProDistributionConnector\HookHandler\PdfExportConvertMathSVGtoPNG::onBSUEModulePDFFindFiles
	 * @return void
	 */
	public function testOnBSUEModulePDFFindFiles() {
		$svgHandler = $this->createMock( SvgHandler::class );
		$svgProvider = $this->createMock( SVGProvider::class );
		$svgProvider
			->expects( $this->once() )
			->method( 'getSvg' )
			->with( 'THEHASH' )
			->will( $this->returnValue( '<svg></svg>' ) );
		$handler = new PdfExportConvertMathSVGtoPNG( $svgHandler, $svgProvider, wfTempDir() );

		$sender = $this->createMock( BsPDFServlet::class );
		$imgEl = $this->createMock( DOMElement::class );
		$imgEl->method( 'getAttribute' )->will(
			$this->returnValueMap( [
				[ 'class', 'mwe-math-fallback-image' ],
				[ 'data-orig-src', 'https://wikiserver/wiki/Special:Math?hash=THEHASH' ],
			] )
		);
		$absFSpath = 'doesnt/matter';
		$fileName = 'same.here';
		$type = 'images';

		$handler->onBSUEModulePDFFindFiles( $sender, $imgEl, $absFSpath, $fileName, $type );
	}

	/**
	 * @covers BlueSpice\ProDistributionConnector\HookHandler\PdfExportConvertMathSVGtoPNG::onBSUEModulePDFFindFiles
	 * @return void
	 */
	public function testOnBSUEModulePDFFindFilesBailOutOnType() {
		$svgHandler = $this->createMock( SvgHandler::class );
		$svgProvider = $this->createMock( SVGProvider::class );
		$svgProvider->expects( $this->never() )->method( 'getSvg' );
		$handler = new PdfExportConvertMathSVGtoPNG( $svgHandler, $svgProvider, wfTempDir() );

		$sender = $this->createMock( BsPDFServlet::class );
		$imgEl = $this->createMock( DOMElement::class );
		$absFSpath = 'doesnt/matter';
		$fileName = 'same.here';
		$type = 'not-images';

		$handler->onBSUEModulePDFFindFiles( $sender, $imgEl, $absFSpath, $fileName, $type );
	}

	/**
	 * @covers BlueSpice\ProDistributionConnector\HookHandler\PdfExportConvertMathSVGtoPNG::onBSUEModulePDFFindFiles
	 * @return void
	 */
	public function testOnBSUEModulePDFFindFilesBailOutOnClass() {
		$svgHandler = $this->createMock( SvgHandler::class );
		$svgProvider = $this->createMock( SVGProvider::class );
		$svgProvider->expects( $this->never() )->method( 'getSvg' );
		$handler = new PdfExportConvertMathSVGtoPNG( $svgHandler, $svgProvider, wfTempDir() );

		$sender = $this->createMock( BsPDFServlet::class );
		$imgEl = $this->createMock( DOMElement::class );
		$imgEl->method( 'getAttribute' )->will(
			$this->returnValue( 'mwe-NOT-math-fallback-image' )
		);
		$absFSpath = 'doesnt/matter';
		$fileName = 'same.here';
		$type = 'images';

		$handler->onBSUEModulePDFFindFiles( $sender, $imgEl, $absFSpath, $fileName, $type );
	}
}
