<?php

use BlueSpice\EchoConnector\FormatterFactory;
use BlueSpice\EchoConnector\UserLocator;
use MediaWiki\MediaWikiServices;

return [
	'BSEchoConnectorUserLocator' => static function ( MediaWikiServices $services ) {
		$context = new DerivativeContext( RequestContext::getMain() );
		$context->setUser(
			$services->getService( 'BSUtilityFactory' )->getMaintenanceUser()->getUser()
		);

		return new UserLocator(
			$services->getDBLoadBalancer(),
			$context,
			$services->getPermissionManager(),
			$services->getHookContainer()
		);
	},
	'BSEchoConnectorFormatterFactory' => static function ( MediaWikiServices $services ) {
		$config = $services->getConfigFactory()->makeConfig( 'bsg' );
		$specs = $config->get( 'EchoEmailFormatterClasses' );
		return new FormatterFactory( $services->getObjectFactory(), $specs );
	},
];
