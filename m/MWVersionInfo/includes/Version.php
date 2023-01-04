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
 * Represents a version in the format
 * major.minor.patch. Note that patch might
 * be "x", to represent a branch of releases
 */
class Version {

	/**
	 * @var int
	 */
	public $major;

	/**
	 * @var int
	 */
	public $minor;

	/**
	 * @var int|string "x" if this represents a branch
	 */
	public $patch;

	/**
	 * @param int|string $major
	 * @param int|string $minor
	 * @param int|string $patch
	 */
	public function __construct( $major, $minor, $patch ) {
		// Cast everything to int out of safety
		$this->major = (int)$major;
		$this->minor = (int)$minor;
		$this->patch = $patch === 'x' ? $patch : (int)$patch;
	}

	/**
	 * @param string $input in ##.##.## format
	 * @throws \Exception
	 * @return bool|Version
	 */
	public static function newFromString( $input ) {
		$matched = preg_match(
			'/^(?P<major>\d+)\.(?P<minor>\d+)(\.(?P<patch>\d+))?$/',
			$input,
			$matches
		);
		if ( !$matched ) {
			// Invalid
			throw new \Exception( "Invalid version: $input" );
		}

		$patch = $matches['patch'] ?? 'x';

		return new self(
			$matches['major'],
			$matches['minor'],
			$patch
		);
	}

	/**
	 * String representation for human output
	 *
	 * @return string
	 */
	public function getPrettyVersion() {
		return "{$this->major}.{$this->minor}.{$this->patch}";
	}

	/**
	 * Get this release's branch (e.g. 1.36)
	 *
	 * @return Version
	 */
	public function getBranch() {
		if ( $this->patch === 'x' ) {
			return $this;
		} else {
			$fam = clone $this;
			$fam->patch = 'x';
			return $fam;
		}
	}

	/**
	 * Git Branch for this version (e.g. REL1_37)
	 *
	 * @return string
	 */
	public function getGitBranch() {
		return "REL{$this->major}_{$this->minor}";
	}

	/**
	 * String representation for database output, zero-padded
	 * so sorting is easier
	 *
	 * @return string
	 */
	public function __toString() {
		// 0-pad minor and patch to 2 digits
		$minor = sprintf( '%02d', $this->minor );
		if ( $this->patch === 'x' ) {
			$patch = '';
		} else {
			$patch = '.' . sprintf( '%02d', $this->patch );
		}
		return "{$this->major}.{$minor}{$patch}";
	}

	/**
	 * Whether it equals the other version exactly or not
	 * @param Version $other
	 * @return bool
	 */
	public function equals( Version $other ) {
		return $this->major === $other->major
			&& $this->minor === $other->minor
			&& $this->patch === $other->patch;
	}
}
