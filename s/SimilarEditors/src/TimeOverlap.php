<?php

namespace MediaWiki\Extension\SimilarEditors;

class TimeOverlap {
	/**
	 * Similarity score
	 *
	 * @var float
	 */
	private $cosineSimilarity;

	/**
	 * Qualitative description of the similarity score
	 * possible values: no overlap, low, medium, high
	 *
	 * @var string
	 */
	private $level;

	/**
	 * @param float $cosineSimilarity
	 * @param string $level
	 */
	public function __construct(
		float $cosineSimilarity,
		string $level
	) {
		$this->cosineSimilarity = $cosineSimilarity;
		$this->level = $level;
	}

	/**
	 * @return float
	 */
	public function getCosineSimilarity(): float {
		return $this->cosineSimilarity;
	}

	/**
	 * @return string
	 */
	public function getLevel(): string {
		return $this->level;
	}
}
