<?php

namespace BlueSpice\ExtendedStatistics\Hook\BSInsertMagicAjaxGetData;

use BlueSpice\InsertMagic\Hook\BSInsertMagicAjaxGetData;

class AddProgressTag extends BSInsertMagicAjaxGetData {

	/**
	 *
	 * @return bool
	 */
	protected function skipProcessing() {
		return $this->type !== 'tags';
	}

	/**
	 *
	 * @return bool
	 */
	protected function doProcess() {
		$this->response->result[] = (object)[
			'id' => 'bs:statistics:progress',
			'type' => 'tag',
			'name' => 'progress',
			'desc' => $this->msg( 'bs-statistics-tag-progress-desc' )->text(),
			'examples' => [
				[ 'code' => '<bs:statistics:progress basecount="200" progressitem="OK" width="150" />' ]
			],
			'previewable' => false,
			'mwvecommand' => 'progressCommand',
			'helplink' => $this->getHelpLink()
		];

		return true;
	}

	/**
	 *
	 * @return string
	 */
	private function getHelpLink() {
		return $this->getServices()->getService( 'BSExtensionFactory' )
			->getExtension( 'BlueSpiceExtendedStatistics' )->getUrl();
	}

}
