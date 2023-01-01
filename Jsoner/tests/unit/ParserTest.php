<?php

namespace jsoner;

use jsoner\exceptions\ParserException;

class ParserTest extends \PHPUnit_Framework_TestCase
{
	public function validJsonProvider() {

		return [[
			'{"Accept-Language": "en-US,en;q=0.8", "Host": "headers.jsontest.com", "Accept-Charset": "ISO-8859-1,utf-8;q=0.7,*;q=0.3", "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8"}',
		]];
	}

	public function invalidJsonProvider() {
		return [[
			' <!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN"> <html><head> <title>401 Unauthorized</title> </head><body> <h1>Unauthorized</h1> <p>This server could not verify that you are authorized to access the document requested.  Either you supplied the wrong credentials (e.g., bad password), or your browser doesn\'t understand how to supply the credentials required.</p> <hr> <address>Apache/redacted (redacted) Server at redacted Port redacted</address> </body></html>',
			'{"Key:"Value"}',
		]];
	}

	/**
	 * @var Parser
	 */
	private $parser;

	protected function setUp() {

		$emptyConfig = new Config( [
			'Parser-ErrorKey' => '_error',
		] );
		$this->parser = new Parser( $emptyConfig );
	}

	/**
	 * @dataProvider validJsonProvider
	 */
	public function testValidJson( $validJson ) {

		$this->parser->parse( $validJson );
	}

	/**
	 * @dataProvider invalidJsonProvider
	 */
	public function testInvalidJson( $invalidJson ) {

		$this->expectException( ParserException::class );
		$this->parser->parse( $invalidJson );
	}

	public function testThatExistingErrorKeyInResponseJsonThrowsException() {

		$this->expectException( ParserException::class );
		$errorKey = $this->parser->getConfig()->getItem( 'Parser-ErrorKey' );
		$this->parser->parse( "{\"$errorKey\": \"Oh no!\"}" );
	}
}
