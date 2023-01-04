<?php

declare( strict_types = 1 );

namespace Doppelganger\Test;

use Scribunto_LuaEngineTestBase;

/**
 * @group Doppelganger
 *
 * @license GPL-2.0-or-later
 *
 * @author John Erling Blad < jeblad@gmail.com >
 */
class DoubleTest extends Scribunto_LuaEngineTestBase {

	protected static $moduleName = 'DoubleTest';

	/**
	 * @slowThreshold 1000
	 * @see Scribunto_LuaEngineTestBase::getTestModules()
	 */
	protected function getTestModules(): array {
		return parent::getTestModules() + [
			'DoubleTest' => __DIR__ . '/DoubleTest.lua'
		];
	}
}
