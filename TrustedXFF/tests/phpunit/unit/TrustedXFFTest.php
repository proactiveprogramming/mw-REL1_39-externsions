<?php

use MediaWiki\Extension\TrustedXFF\TrustedXFF;

/**
 * @covers MediaWiki\Extension\TrustedXFF\TrustedXFF
 */
class TrustedXFFTest extends MediaWikiUnitTestCase {

	protected function setUp(): void {
		parent::setup();
	}

	public function provideIPs() {
		return [
			[ '64.12.96.1', true, "IPv4 address trusted because it's in the AOL range 64.12.96.0/19" ],
			[ '127.0.0.2', false, 'IPv4 address not trusted' ],
			[ '2001:4c28:1::1', true, "IPv6 address trusted because it's in the Opera Mini range 2001:4c28:1::/48" ],
			[ '2001:4c28:2::1', false, 'IPv6 address not trusted' ],
		];
	}

	/**
	 * @dataProvider provideIPs
	 */
	public function testisTrusted( $ip, $expectedTrusted, $msg ) {
		$trusted = false;
		TrustedXFF::getInstance()->onIsTrustedProxy( $ip, $trusted );

		$this->assertEquals( $expectedTrusted, $trusted, $msg );
	}

}
