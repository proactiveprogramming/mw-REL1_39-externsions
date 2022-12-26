<?php

namespace MediaWiki\Extension\SimilarEditors;

use Language;
use MediaWiki\User\UserFactory;

class ResultsFormatterFactory {
	/** @var UserFactory */
	private $userFactory;

	/**
	 * @param UserFactory $userFactory
	 */
	public function __construct( UserFactory $userFactory ) {
		$this->userFactory = $userFactory;
	}

	/**
	 * @param Language $language
	 * @return ResultsFormatter
	 */
	public function createFormatter(
		Language $language
	): ResultsFormatter {
		return new ResultsFormatter( $this->userFactory, $language );
	}
}
