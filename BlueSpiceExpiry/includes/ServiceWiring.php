<?php

use BlueSpice\Expiry\SpecialLogLogger;
use MediaWiki\MediaWikiServices;

return [

	'BSExpiryFactory' => static function ( MediaWikiServices $services ) {
		return new \BlueSpice\Expiry\Factory;
	},

	'BSExpirySpecialLogLogger' => static function ( MediaWikiServices $services ) {
		return new SpecialLogLogger();
	},
];
