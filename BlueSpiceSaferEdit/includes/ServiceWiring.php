<?php

use BlueSpice\ExtensionAttributeBasedRegistry;
use BlueSpice\SaferEdit\SaferEditManager;
use MediaWiki\MediaWikiServices;

return [
	'BSSaferEditManager' => static function ( MediaWikiServices $services ) {
		$db = $services->getDBLoadBalancer()->getConnection( DB_PRIMARY );
		$context = RequestContext::getMain();
		$registry = new ExtensionAttributeBasedRegistry( 'BlueSpiceSaferEditEnvironmentCheckers' );
		return new SaferEditManager( $db, $context, $registry );
	},
];
