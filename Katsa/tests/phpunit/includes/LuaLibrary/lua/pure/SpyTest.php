<?php

declare( strict_types = 1 );

namespace Katsa\Test;

use Scribunto_LuaEngineTestBase;

/**
 * @group Katsa
 *
 * @license GPL-2.0-or-later
 *
 * @author John Erling Blad < jeblad@gmail.com >
 */
class SpyTest extends Scribunto_LuaEngineTestBase {

	protected static $moduleName = 'SpyTest';

	/**
	 * @slowThreshold 1000
	 * @see Scribunto_LuaEngineTestBase::getTestModules()
	 */
	protected function getTestModules(): array {
		return parent::getTestModules() + [
			'SpyTest' => __DIR__ . '/SpyTest.lua'
		];
	}
}
