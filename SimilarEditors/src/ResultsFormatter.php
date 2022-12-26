<?php

namespace MediaWiki\Extension\SimilarEditors;

use Html;
use Language;
use Linker;
use MediaWiki\User\UserFactory;
use MediaWiki\User\UserRigorOptions;
use Message;

class ResultsFormatter {
	/**
	 * Properties, corresponding to table columns
	 */
	private const PROPERTIES = [
		'user',
		'day-overlap',
		'hour-overlap',
		'edit-overlap',
		'inverse-edit-overlap',
		'edits',
	];

	/** @var UserFactory */
	private $userFactory;

	/** @var Language */
	private $language;

	/**
	 * @param UserFactory $userFactory
	 * @param Language $language
	 */
	public function __construct(
		UserFactory $userFactory,
		Language $language
	) {
		$this->userFactory = $userFactory;
		$this->language = $language;
	}

	/**
	 * @param string $target
	 * @param Neighbor[] $neighbors
	 * @return string
	 */
	public function formatResults( string $target, array $neighbors ): string {
		$headCells = '';
		foreach ( self::PROPERTIES as $property ) {
			// For grepping. The following messages can be used here:
			// * similareditors-results-user
			// * similareditors-results-day-overlap
			// * similareditors-results-hour-overlap
			// * similareditors-results-edit-overlap
			// * similareditors-results-inverse-edit-overlap
			// * similareditors-results-edits
			$headCellText = $this->msg( 'similareditors-results-' . $property )->parse();
			$headCells .= Html::rawElement( 'th', [], $headCellText );
		}
		$headRow = Html::rawElement( 'tr', [], $headCells );
		$head = Html::rawElement( 'thead', [], $headRow );

		$bodyRows = '';
		foreach ( $neighbors as $neighbor ) {
			$bodyRows .= $this->formatRow( $target, $neighbor );
		}
		$body = Html::rawElement( 'tbody', [], $bodyRows );

		return Html::rawElement(
			'table',
			[ 'class' => 'mw-datatable sortable' ],
			$head . $body
		);
	}

	/**
	 * @param string $target
	 * @param Neighbor $neighbor
	 * @return string
	 */
	private function formatRow( string $target, Neighbor $neighbor ): string {
		$row = Html::openElement( 'tr', [] );

		foreach ( self::PROPERTIES as $property ) {
			$row .= Html::rawElement( 'td', [], $this->formatRowProperty( $target, $neighbor, $property ) ) . "\n";
		}

		$row .= Html::closeElement( 'tr' );

		return $row;
	}

	/**
	 * @param string $target
	 * @param Neighbor $neighbor
	 * @param string $property
	 * @return string
	 */
	private function formatRowProperty(
		string $target,
		Neighbor $neighbor,
		string $property
	): string {
		switch ( $property ) {
			case 'user':
				$user = $this->userFactory->newFromName(
					$neighbor->getUserText(),
					// May be an IP address
					UserRigorOptions::RIGOR_NONE
				);
				// TODO: revert as part of T309675
				return Linker::userLink( 0, 'en>' . $user->getName() ) .
					' ' . $this->msg( 'parentheses-start' ) .
					// @phan-suppress-next-line SecurityCheck-DoubleEscaped
					Linker::makeExternalLink(
						'https://interaction-timeline.toolforge.org/' .
							'?wiki=enwiki&user=' . urlencode( $target ) .
							'&user=' . urlencode( $user->getName() ),
						$this->msg( 'similareditors-results-user-timeline' )->parse(),
						false
					) .
					$this->msg( 'parentheses-end' );
			case 'day-overlap':
				return $this->msg(
					$this->getOverlapMessageKey( $neighbor->getDayOverlap()->getLevel() )
				)->parse();
			case 'hour-overlap':
				return $this->msg(
					$this->getOverlapMessageKey( $neighbor->getHourOverlap()->getLevel() )
				)->parse();
			case 'edit-overlap':
				return (string)$neighbor->getEditOverlap();
			case 'inverse-edit-overlap':
				return (string)$neighbor->getEditOverlapInv();
			case 'edits':
				return (string)$neighbor->getNumEditsInData();
		}
	}

	/**
	 * @param string $level
	 * @return string
	 */
	private function getOverlapMessageKey( string $level ): string {
		// For grepping. The following messages can be used here:
		// * similareditors-overlap-level-no-overlap
		// * similareditors-overlap-level-low
		// * similareditors-overlap-level-medium
		// * similareditors-overlap-level-high
		return 'similareditors-overlap-level-' . strtolower( str_replace( ' ', '-', $level ) );
	}

	/**
	 * @param string $key
	 * @param array $params
	 * @return Message
	 */
	private function msg( string $key, array $params = [] ): Message {
		return new Message( $key, $params, $this->language );
	}
}
