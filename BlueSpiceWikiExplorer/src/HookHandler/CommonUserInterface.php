<?php

namespace BlueSpice\WikiExplorer\HookHandler;

use BlueSpice\WikiExplorer\GlobalActionsTool;
use MWStake\MediaWiki\Component\CommonUserInterface\Hook\MWStakeCommonUIRegisterSkinSlotComponents;

class CommonUserInterface implements MWStakeCommonUIRegisterSkinSlotComponents {

	/**
	 * @inheritDoc
	 */
	public function onMWStakeCommonUIRegisterSkinSlotComponents( $registry ): void {
		$registry->register(
			'GlobalActionsTools',
			[
				'special-bluespice-wikiexplorer' => [
					'factory' => static function () {
						return new GlobalActionsTool();
					}
				]
			]
		);
	}
}
