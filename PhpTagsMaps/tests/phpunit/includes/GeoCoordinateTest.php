<?php

// namespace MultiMaps;

class GeoCoordinateTest extends \PHPUnit\Framework\TestCase {

	public function provideGetLatLonFromString() {
		// $string, $expected
		yield [ "55.755831°, 37.617673°", [ "lat" => 55.755831, "lon" => 37.617673 ] ];
		yield [ "N55.755831°, E37.617673°", [ "lat" => 55.755831, "lon" => 37.617673 ] ];
		yield [ "55°45.34986'N, 37°37.06038'E", [ "lat" => 55.755831, "lon" => 37.617673 ] ];
		yield [ "55°45'20.9916\"N, 37°37'3.6228\"E", [ "lat" => 55.755831, "lon" => 37.617673 ] ];
		yield [ " 37°37'3.6228\"E, 55°45'20.9916\" ", [ "lat" => 55.755831, "lon" => 37.617673 ] ];
		yield [ " 37°37'3.6228\", 55°45'20.9916\" N ", [ "lat" => 55.755831, "lon" => 37.617673 ] ];
		yield [ "55°45'20.9916\"N, 37°37'3.6228\"", [ "lat" => 55.755831, "lon" => 37.617673 ] ];
		yield [ "55°45'20.9916\", E37°37'3.6228\"", [ "lat" => 55.755831, "lon" => 37.617673 ] ];
		yield [ " 10  , - 10 ", [ "lat" => 10.0, "lon" => -10.0 ] ];
		yield [ "-10°,s10 °  ", [ "lat" => -10.0, "lon" => -10.0 ] ];
		yield [ "s10.123456°,  -1.123°   ", [ "lat" => -10.123456, "lon" => -1.123 ] ];
		yield [ "10.123456° N,  1.123° W  ", [ "lat" => 10.123456, "lon" => -1.123 ] ];
		yield [ "10.12° W,  1.123° s  ", [ "lat" => -1.123, "lon" => -10.12 ] ];
		yield [ "10.12° w,  1.123°", [ "lat" => 1.123, "lon" => -10.12 ] ];
		yield [ "Z10.12°,  1.123°", false ];
		yield [ "10.12°, X1.123°", false ];
		yield [ "Tralala", false ];
	}

	/**
	 * @dataProvider provideGetLatLonFromString
	 * @covers MultiMaps\GeoCoordinate::getLatLonFromString
	 */
	public function testGetLatLonFromString( $string, $expected ) {
		$this->assertSame( $expected, MultiMaps\GeoCoordinate::getLatLonFromString( $string ) );
	}

}
