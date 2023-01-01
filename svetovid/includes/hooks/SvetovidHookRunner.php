<?php

use MediaWiki\HookContainer\HookContainer;
use MediaWiki\MediaWikiServices;

class SvetovidHookRunner implements SvetovidAddLinksHook {
	private HookContainer $hookContainer;

	public function __construct() {
		$this->hookContainer = MediaWikiServices::getInstance()->getHookContainer();
	}

	public function onSvetovidAddLinks(
		Title $targetTitle, WikiPage $page, array $texts, string &$text, int &$changes
	) : bool {
		return $this->hookContainer->run(
			'SvetovidAddLinks',
			[ $targetTitle, $page, $texts, &$text, &$changes ]
		);
	}
}
