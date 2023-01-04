<?php

use jsoner\Helper;
use jsoner\Jsoner;

/**
 * Hooks for the Jsoner extension.
 *
 * @ingroup Extensions
 */
class JsonerHooks
{
	private static $configPrefix = 'jsoner';
	private static $requestCache = [];

	public static function onParserSetup( &$parser ) {
		try {
			Helper::assertExtensionsInstalled( ['curl', 'intl', 'fileinfo', 'mbstring'] );
		} catch ( Exception $e ) {
			return Helper::errorMessage( $e->getMessage() );
		}

		$parser->setFunctionHook( 'jsoner', 'JsonerHooks::run' );

		// Always return true, in order not to stop MW's hook processing!
		return true;
	}

	/**
	 * Provides a callback for configuration in extension.json
	 * @return GlobalVarConfig The configuration for the Jsoner extension
	 */
	public static function buildConfig() {
		return new GlobalVarConfig( self::$configPrefix );
	}

	public static function run( \Parser &$parser ) {
		$parser->disableCache();

		$config = self::getConfig();
		$options = Helper::extractOptions( array_slice( func_get_args(), 1 ) );

		$jsoner = new Jsoner($config, $options);

		return [$jsoner->run(self::$requestCache), 'noparse' => false];
	}

	private static function getConfig() {
		return ConfigFactory::getDefaultInstance()->makeConfig( self::$configPrefix );
	}
}
