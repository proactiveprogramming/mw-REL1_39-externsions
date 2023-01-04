<?php

namespace MediaWiki\Extension\MenuEditor;

use Config;
use ResourceLoaderContext;

class ClientConfig {

	/**
	 *
	 * @param ResourceLoaderContext $context
	 * @param Config $config
	 * @return array
	 */
	public static function makeConfigJson(
		ResourceLoaderContext $context,
		Config $config
	) {
		$allowedMediawikiSidebarKeywords = $config->get( 'MenuEditorMediawikiSidebarAllowedKeywords' );
		return [
			'allowedMediawikiSidebarKeywords' => $allowedMediawikiSidebarKeywords
		];
	}
}
