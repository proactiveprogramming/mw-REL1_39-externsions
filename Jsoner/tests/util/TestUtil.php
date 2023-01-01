<?php

namespace jsoner;

class TestUtil {
	public static function makeIntegrationTestUrl( $query ) {
		return sprintf( "http://%s:%d/$query.json",
			WEB_SERVER_HOST,
			WEB_SERVER_PORT
		);
	}
}
