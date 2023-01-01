<?php
/**
 * PropChainsHelper extension hooks
 *
 * @file
 * @ingroup Extensions
 * @license GPL-2.0+
 */
class PropChainsHelperHooks {
	/**
	 * Conditionally register the unit testing module for the ext.propChainsHelper module
	 * only if that module is loaded
	 *
	 * @param array $testModules The array of registered test modules
	 * @param ResourceLoader $resourceLoader The reference to the resource loader
	 * @return true
	 */
	public static function onResourceLoaderTestModules( array &$testModules, ResourceLoader &$resourceLoader ) {
		$testModules['qunit']['ext.propChainsHelper.tests'] = [
			'scripts' => [
				'tests/PropChainsHelper.test.js'
			],
			'dependencies' => [
				'ext.propChainsHelper'
			],
			'localBasePath' => __DIR__,
			'remoteExtPath' => 'PropChainsHelper',
		];
		return true;
	}
}
