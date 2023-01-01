<?php

namespace TimedNotify\Tests\Integration;

use ExtensionRegistry;
use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;

/**
 * @coversNothing
 */
class ServiceWiringTest extends MediaWikiIntegrationTestCase {
	public function testServicesDoNotFatal() {
		$this->expectNotToPerformAssertions();
		$services = $this->getServicesNames();

		foreach ( $services as $service ) {
			MediaWikiServices::getInstance()->getService( $service );
		}
	}

	/**
	 * Returns the names of all WikiGuard services.
	 *
	 * @return array
	 */
	private function getServicesNames(): array {
		$allThings = ExtensionRegistry::getInstance()->getAllThings();
		$dirName = dirname( $allThings['TimedNotify']['path'] );

		return array_keys( require $dirName . '/TimedNotify.wiring.php' );
	}
}
