<?php

namespace MediaWiki\Extension\EnhancedUpload\Hook\BSInsertMagicAjaxGetData;

use BlueSpice\InsertMagic\Hook\BSInsertMagicAjaxGetData;

class AddAttachmentTag extends BSInsertMagicAjaxGetData {

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
			'id' => 'attachments',
			'type' => 'tag',
			'name' => 'attachments',
			'desc' => $this->msg( 'enhancedupload-attachment-tag-desc' )->text(),
			'code' => "<attachments></attachments>",
			'mwvecommand' => 'attachmentCommand',
			'previewable' => false,
			'examples' => [ [
				'code' => '<attachments title="AttachmentsTag" prefix="Test" categories="Attachments"
				 skip-options="1"></ attachments>'
			]
		],
			// 'helplink' => $this->getHelpLink()
		];

		return true;
	}

	/**
	 *
	 * @return string
	 */
	private function getHelpLink() {
		return $this->getServices()->getService( 'BSExtensionFactory' )
			->getExtension( 'EnhancedUpload' )->getUrl();
	}

}
