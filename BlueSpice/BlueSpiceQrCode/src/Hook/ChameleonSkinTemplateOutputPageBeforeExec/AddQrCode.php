<?php

namespace BlueSpice\QrCode\Hook\ChameleonSkinTemplateOutputPageBeforeExec;

use BlueSpice\Hook\ChameleonSkinTemplateOutputPageBeforeExec;
use BlueSpice\QrCode\Panel\Panel;
use BlueSpice\SkinData;

class AddQrCode extends ChameleonSkinTemplateOutputPageBeforeExec {

	protected function skipProcessing() {
		$title = $this->skin->getTitle();
		if ( $title instanceof \Title == false || $title->exists() === false ) {
			return true;
		}
		return false;
	}

	protected function doProcess() {
		$this->mergeSkinDataArray(
			SkinData::PAGE_TOOLS_PANEL,
			[
				'qrcode' => [
					'position' => 100,
					'callback' => static function ( $sktemplate ) {
						return new Panel( $sktemplate );
					}
				]
			]
		);

		return true;
	}
}
