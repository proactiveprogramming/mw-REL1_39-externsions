<?php

namespace BlueSpice\Expiry\HookHandler;

use BlueSpice\Expiry\GlobalActionsTool;
use MWStake\MediaWiki\Component\CommonUserInterface\Hook\MWStakeCommonUIRegisterSkinSlotComponents;

class CommonUserInterface implements MWStakeCommonUIRegisterSkinSlotComponents {

	/**
	 * @inheritDoc
	 */
	public function onMWStakeCommonUIRegisterSkinSlotComponents( $registry ): void {
		$registry->register(
			'GlobalActionsTools',
			[
				'special-bluespice-expiry' => [
					'factory' => static function () {
						return new GlobalActionsTool();
					}
				]
			]
		);
	}

}
