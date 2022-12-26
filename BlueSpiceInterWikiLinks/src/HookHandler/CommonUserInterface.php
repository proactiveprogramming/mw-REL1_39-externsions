<?php

namespace BlueSpice\InterWikiLinks\HookHandler;

use BlueSpice\InterWikiLinks\GlobalActionsManager;
use MWStake\MediaWiki\Component\CommonUserInterface\Hook\MWStakeCommonUIRegisterSkinSlotComponents;

class CommonUserInterface implements MWStakeCommonUIRegisterSkinSlotComponents {

	/**
	 * @inheritDoc
	 */
	public function onMWStakeCommonUIRegisterSkinSlotComponents( $registry ): void {
		$registry->register(
			'GlobalActionsManager',
			[
				'special-bluespice-interwikilinks' => [
					'factory' => static function () {
						return new GlobalActionsManager();
					}
				]
			]
		);
	}
}
