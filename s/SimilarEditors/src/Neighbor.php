<?php

namespace MediaWiki\Extension\SimilarEditors;

class Neighbor {

	/**
	 * User name of the neighbor
	 * @var string
	 */
	private $userText;

	/**
	 * Number of edits made by the neighbor in the data
	 *
	 * @var int
	 */
	private $numEditsInData;

	/**
	 * Number of overlapping edited pages divided by number of pages edited by queried editor (between 0 and 1)
	 *
	 * @var float
	 */
	private $editOverlap;

	/**
	 * Number of overlapping edited pages divided by number of pages edited by the neighbor (between 0 and 1)
	 *
	 * @var float
	 */
	private $editOverlapInv;

	/**
	 * Level of temporal overlap (editing the same days of the week) with queried editor
	 *
	 * @var TimeOverlap
	 */
	private $dayOverlap;

	/**
	 * Level of temporal overlap (editing the same hours of the day) with queried editor
	 * @var TimeOverlap
	 */
	private $hourOverlap;

	/**
	 * Additional tool links in API response for follow-up on data
	 * @var string[]
	 */
	private $followUp;

	/**
	 * @param string $userText
	 * @param int $numEditsInData
	 * @param float $editOverlap
	 * @param float $editOverlapInv
	 * @param TimeOverlap $dayOverlap
	 * @param TimeOverlap $hourOverlap
	 * @param string[] $followUp
	 */
	public function __construct(
		$userText,
		$numEditsInData,
		$editOverlap,
		$editOverlapInv,
		$dayOverlap,
		$hourOverlap,
		$followUp
	) {
		$this->userText = $userText;
		$this->numEditsInData = $numEditsInData;
		$this->editOverlap = $editOverlap;
		$this->editOverlapInv = $editOverlapInv;
		$this->dayOverlap = $dayOverlap;
		$this->hourOverlap = $hourOverlap;
		$this->followUp = $followUp;
	}

	/**
	 * @return string
	 */
	public function getUserText(): string {
		return $this->userText;
	}

	/**
	 * @return int
	 */
	public function getNumEditsInData(): int {
		return $this->numEditsInData;
	}

	/**
	 * @return float
	 */
	public function getEditOverlap(): float {
		return $this->editOverlap;
	}

	/**
	 * @return float
	 */
	public function getEditOverlapInv(): float {
		return $this->editOverlapInv;
	}

	/**
	 * @return TimeOverlap
	 */
	public function getDayOverlap(): TimeOverlap {
		return $this->dayOverlap;
	}

	/**
	 * @return TimeOverlap
	 */
	public function getHourOverlap(): TimeOverlap {
		return $this->hourOverlap;
	}

	/**
	 * @return string[]
	 */
	public function getFollowUp(): array {
		return $this->followUp;
	}
}
