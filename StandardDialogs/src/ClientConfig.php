<?php

namespace MediaWiki\Extension\StandardDialogs;

use Config;
use MediaWiki\MediaWikiServices;
use ResourceLoaderContext;

class ClientConfig {
	/**
	 *
	 * @param ResourceLoaderContext $context
	 * @param Config $config
	 * @param array $param
	 * @return void
	 */
	public static function makePageInfoPanelModuleRegistryJson(
		ResourceLoaderContext $context,
		Config $config,
		$param
	) {
		$hookContainer = MediaWikiServices::getInstance()->getHookContainer();
		$modules = [];
		$hookContainer->run( 'StandardDialogsRegisterPageInfoPanelModules', [ &$modules, $context, $config ] );
		return $modules;
	}
}
