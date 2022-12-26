<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Hooks;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use MediaWiki\Hook\MediaWikiServicesHook;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\SlotRoleRegistry;
use MediaWiki\WikispeechSpeechDataCollector\Crud\Mcr\RecordingAnnotationsCrud;

/**
 * Registers multi content revision slot roles,
 * i.e. name and content type of slots used by this extension.
 *
 * @since 0.1.0
 */
class McrHooks implements MediaWikiServicesHook {
	/**
	 * @param MediaWikiServices $services
	 * @return bool|void
	 * @since 0.1.0
	 */
	public function onMediaWikiServices( $services ) {
		$services->addServiceManipulator(
			'SlotRoleRegistry',
			static function ( SlotRoleRegistry $registry ) {
				$registry->defineRoleWithModel(
					RecordingAnnotationsCrud::SLOT_ROLE,
					CONTENT_MODEL_JSON
				);
			}
		);
	}
}
