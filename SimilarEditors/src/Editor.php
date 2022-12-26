<?php

namespace MediaWiki\Extension\SimilarEditors;

class Editor {

	/**
	 * User name from the query usertext parameter. Reformatted according to mediawiki's "User" naming convention.
	 *
	 * @var string|null
	 */
	private $userText;

	/**
	 * Number of in-scope edits made by the user in the data
	 *
	 * @var int|null
	 */
	private $numEditsInData;

	/**
	 * Timestamp of the first (oldest) edit made by the user in the data
	 *
	 * @var string|null
	 */
	private $firstEditInData;

	/**
	 * Timestamp of the last (most recent) edit made by the user in the data
	 *
	 * @var string|null
	 */
	private $lastEditInData;

	/**
	 * @param string|null $userText
	 * @param int|null $numEditsInData
	 * @param string|null $firstEditInData
	 * @param string|null $lastEditInData
	 */
	public function __construct(
		$userText = null,
		$numEditsInData = null,
		$firstEditInData = null,
		$lastEditInData = null
	) {
		$this->userText = $userText;
		$this->numEditsInData = $numEditsInData;
		$this->firstEditInData = $firstEditInData;
		$this->lastEditInData = $lastEditInData;
	}

	/**
	 * @return string|null
	 */
	public function getUserText(): ?string {
		return $this->userText;
	}

	/**
	 * @return int|null
	 */
	public function getNumEditsInData(): ?int {
		return $this->numEditsInData;
	}

	/**
	 * @return string|null
	 */
	public function getFirstEditInData(): ?string {
		return $this->firstEditInData;
	}

	/**
	 * @return string|null
	 */
	public function getLastEditInData(): ?string {
		return $this->lastEditInData;
	}
}
