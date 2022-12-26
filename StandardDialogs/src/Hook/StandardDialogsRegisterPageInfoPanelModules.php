<?php

namespace MediaWiki\Extension\StandardDialogs\Hook;

use Config;
use ResourceLoaderContext;

interface StandardDialogsRegisterPageInfoPanelModules {

	/**
	 *
	 * @param string[] &$modules
	 * @param ResourceLoaderContext $context
	 * @param Config $config
	 * @return void
	 */
	public function onStandardDialogsRegisterPageInfoPanelModules(
		&$modules, ResourceLoaderContext $context, Config $config
	): void;

}
