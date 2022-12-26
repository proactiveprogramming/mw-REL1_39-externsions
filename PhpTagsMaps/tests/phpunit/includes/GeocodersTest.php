<?php

namespace MultiMaps;

/**
 * @covers \MultiMaps\Geocoders
 */
class GeocodersTest extends \PHPUnit\Framework\TestCase {

	public function testReturnFalseOnUnknownService() {
		$geocoders = new Geocoders();
		$this->assertFalse( $geocoders->getCoordinates( '', '' ) );
		$this->assertFalse( $geocoders->getCoordinates( '', 'blablabla' ) );
	}

}
