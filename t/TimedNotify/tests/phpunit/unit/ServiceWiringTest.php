<?php

namespace TimedNotify\Tests\Unit;

use ExtensionRegistry;
use MediaWikiUnitTestCase;

/**
 * @coversNothing
 */
class ServiceWiringTest extends MediaWikiUnitTestCase {
	public function testServicesSortedAlphabetically() {
		$servicesNames = $this->getServicesNames();
		$sortedServices = $servicesNames;
		natcasesort( $sortedServices );

		$this->assertSame( $sortedServices, $servicesNames,
			'Please keep services names sorted alphabetically' );
	}

	public function testServicesArePrefixed() {
		$servicesNames = $this->getServicesNames();

		foreach ( $servicesNames as $serviceName ) {
			$this->assertStringStartsWith( 'TimedNotify.', $serviceName,
				'Please prefix services names with "TimedNotify."' );
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
