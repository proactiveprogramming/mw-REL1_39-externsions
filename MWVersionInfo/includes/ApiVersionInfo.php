<?php
/**
 * Copyright (C) 2016, 2021 Kunal Mehta <legoktm@debian.org>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace MediaWiki\VersionInfo;

use ApiBase;
use ApiMain;

class ApiVersionInfo extends ApiBase {
	/** @inheritDoc */
	public function __construct( ApiMain $mainModule ) {
		parent::__construct( $mainModule, 'mwversioninfo' );
	}

	/** @inheritDoc */
	public function execute() {
		$params = $this->extractRequestParams();
		try {
			$version = Version::newFromString( $params['version'] );
		} catch ( \Exception $e ) {
			$this->dieWithError( 'Invalid MediaWiki version provided', 'invalidversion' );
		}

		$lookup = new ReleaseLookup();
		$info = $lookup->getLatestReleaseFor( $version );
		$result = $this->getResult();
		if ( $info ) {
			if ( $info['version']->equals( $version ) ) {
				// Up to date!!
				$result->addValue( 'mwversioninfo', 'status', 'up-to-date' );
			} else {
				$result->addValue( 'mwversioninfo', 'status', 'outdated' );
				$result->addValue( 'mwversioninfo', 'latest', $info['version']->getPrettyVersion() );
			}
		} else {
			// If we have no info about it, you're running an obsolete version
			$latest = $lookup->getLatestRelease();
			$result->addValue( 'mwversioninfo', 'status', 'obsolete' );
			$result->addValue( 'mwversioninfo', 'latest', $latest['version']->getPrettyVersion() );
		}
	}

	/** @inheritDoc */
	public function getAllowedParams() {
		return [
			'version' => [
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			],
		];
	}

	/** @inheritDoc */
	protected function getExamplesMessages() {
		return [
			'action=mwversioninfo&version=1.27' => 'apihelp-mwversioninfo-example',
		];
	}
}
