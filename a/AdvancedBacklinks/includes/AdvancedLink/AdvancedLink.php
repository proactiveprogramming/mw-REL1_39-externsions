<?php
/**
 * AdvancedBacklinks
 * Copyright (C) 2019  Ostrzyciel
 *
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
 */

abstract class AdvancedLink {

	/**
	 * @var Title
	 */
	public $target;

	/**
	 * @var Title
	 */
	public $through;

	/**
	 * @var Title
	 */
	public $from;

	/** @var bool */
	public $hidden;

	/**
	 * AdvancedLink constructor.
	 *
	 * @param Title $from
	 * @param Title $target
	 * @param Title|null $through
	 * @param bool $hidden
	 */
	public function __construct( Title $from, Title $target, Title $through = null, bool $hidden = false ) {
		$this->target = $target;
		$this->through = $through;
		$this->from = $from;
		$this->hidden = $hidden;
	}

	/**
	 * Returns page ID of the page the link is coming from.
	 * @return int
	 */
	public function getFromID() {
		$fromID = $this->from->getArticleID();
		return $fromID < 0 ? 0 : $fromID;
	}

	/**
	 * Returns page ID of the page the link is pointing to.
	 * @return int
	 */
	public function getTargetID() {
		$targetID = $this->target->getArticleID();
		return $targetID < 0 ? 0 : $targetID;
	}

	/**
	 * Returns page ID of the page the link is transcluded through.
	 * @return int
	 */
	public function getThroughID() {
		if ( !$this->through || $this->hidden ) {
			return 0;
		}
		$throughID = $this->through->getArticleID();
		return $throughID < 0 ? 0 : $throughID;
	}

	/**
	 * @return int
	 */
	public function getHiddenThroughID() {
		if ( !$this->through || !$this->hidden ) {
			return 0;
		}
		$throughID = $this->through->getArticleID();
		return $throughID < 0 ? 0 : $throughID;
	}

	/**
	 * Checks whether two AdvancedLink objects are the same.
	 * @param AdvancedLink $other
	 * @return bool
	 */
	public function isEqualTo( AdvancedLink $other ) {
		return
			$this->getFromID() === $other->getFromID() &&
			$this->target->getDBkey() === $other->target->getDBkey() &&
			$this->target->getNamespace() === $other->target->getNamespace() &&
			$this->getThroughID() === $other->getThroughID() &&
			$this->getHiddenThroughID() === $other->getHiddenThroughID();
	}

	public abstract static function newFromDBRow( $dbRow ) : ?AdvancedLink;

	public abstract function getTextForLogs() : string;
}