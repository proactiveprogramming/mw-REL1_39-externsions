<?php

declare( strict_types=1 );

/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 * @file
 */

namespace MediaWiki\Extension\WebP\Hooks;

use Config;
use ConfigException;
use ImagickException;
use JobQueueGroup;
use MediaWiki\Extension\WebP\Repo\LocalWebPFileRepo;
use MediaWiki\Extension\WebP\TransformWebPImageJob;
use MediaWiki\Extension\WebP\WebPTransformer;
use MediaWiki\Hook\UploadCompleteHook;
use MediaWiki\MediaWikiServices;
use RuntimeException;
use UploadBase;

class MainHooks implements UploadCompleteHook {
	/**
	 * @var Config
	 */
	private $mainConfig;

	/**
	 * FileHooks constructor.
	 *
	 * @param Config $mainConfig
	 */
	public function __construct( Config $mainConfig ) {
		$this->mainConfig = $mainConfig;
	}

	/**
	 * Registers the extension as a local file repo
	 */
	public static function setup(): void {
		global $wgLocalFileRepo, $wgGenerateThumbnailOnParse;

		$wgLocalFileRepo['class'] = LocalWebPFileRepo::class;
		$wgLocalFileRepo['name'] = 'local';
		$wgLocalFileRepo['transformVia404'] = !$wgGenerateThumbnailOnParse;
		$wgLocalFileRepo['backend'] = $wgLocalFileRepo['name'] . '-backend';
	}

	/**
	 * Create a WebP version of the uploaded file
	 *
	 * @param UploadBase $uploadBase
	 */
	public function onUploadComplete( $uploadBase ): void {
		try {
			if ( $this->mainConfig->get( 'WebPEnableConvertOnUpload' ) === false ) {
				return;
			}
		} catch ( ConfigException $e ) {
			return;
		}

		if ( $uploadBase->getLocalFile() === null || !WebPTransformer::canTransform( $uploadBase->getLocalFile() ) ) {
			return;
		}

		try {
			$transformer = new WebPTransformer( $uploadBase->getLocalFile(), [ 'overwrite' => true ] );
		} catch ( RuntimeException $e ) {
			return;
		}

		try {
			if ( $this->mainConfig->get( 'WebPConvertInJobQueue' ) === true ) {
				if ( method_exists( MediaWikiServices::class, 'getJobQueueGroupFactory' ) ) {
					$group = MediaWikiServices::getInstance()->getJobQueueGroupFactory()->makeJobQueueGroup();
				} else {
					$group = JobQueueGroup::singleton();
				}

				$group->push(
					new TransformWebPImageJob(
						$uploadBase->getTitle(),
						[
							'title' => $uploadBase->getTitle(),
							'overwrite' => true,
						]
					)
				);

				return;
			}
		} catch ( ConfigException $e ) {
			return;
		}

		try {
			$transformer->transform();
		} catch ( ImagickException $e ) {
			wfLogWarning( $e->getMessage() );

			return;
		}
	}
}
