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

/**
 * A release is typed as ['version' => Version, 'date' => string]
 */
class ReleaseLookup {

	/** @var ?array */
	private $cfg;

	private function load() {
		if ( $this->cfg ) {
			// Already loaded
			return;
		}

		$msg = wfMessage( 'mwversioninfo.json' )->plain();
		$raw = json_decode( $msg, true );
		if ( !$raw ) {
			// TODO: Add stricter validation here
			throw new \Exception( "Invalid mwversioninfo.json value" );
		}
		$this->cfg = [
			'releases' => [],
			'beta' => $raw['beta'],
		];
		// Make it easier to lookup by branch
		foreach ( $raw['releases'] as $release ) {
			$version = Version::newFromString( $release['version'] );
			$branch = (string)$version->getBranch();
			$this->cfg['releases'][$branch] = [
				'version' => $version,
				'date' => $release['date'],
			];
		}
	}

	/**
	 * Get the latest release that matches the given branch
	 *
	 * @param Version $version
	 * @return array|false if not found
	 */
	public function getLatestReleaseFor( Version $version ) {
		$this->load();
		$branch = (string)$version->getBranch();

		return $this->cfg['releases'][$branch] ?? false;
	}

	/**
	 * Get the most recent overall release
	 * @return array
	 * @suppress PhanTypeArraySuspiciousNullable Okay after load()
	 */
	public function getLatestRelease() {
		$this->load();
		$branch = max( array_keys( $this->cfg['releases'] ) );
		return $this->cfg['releases'][$branch];
	}
}
