<?php

namespace MediaWiki\Extension\UnifiedTaskOverview;

use SkinTemplate;
use Title;

class Setup {
	/**
	 * @param array &$personal_urls
	 * @param Title $title
	 * @param SkinTemplate $skin
	 */
	public static function onPersonalUrls( array &$personal_urls, Title $title, SkinTemplate $skin ) {
		$personal_urls['mytasks'] = [
			"text" => $skin->msg( 'unifiedtaskoverview-personal-url-my-tasks' )->text(),
			"href" => \SpecialPage::getTitleFor( 'UnifiedTaskOverview' )->getLocalURL(),
			"active" => false,
			'data' => [ 'attentionindicator' => 'taskoverview' ],
			'position' => 10,
		];
	}
}
