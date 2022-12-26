<?php

namespace BlueSpice\ExtendedStatistics;

class SnapshotDateRange {
	/** @var SnapshotDate */
	private $from;
	/** @var SnapshotDate */
	private $to;

	/**
	 * @param array $data
	 * @param string $format
	 * @return static
	 */
	public static function newFromFilterData( $data, $format = 'Ymd' ) {
		if ( $format === 'Ymd' ) {
			return new static(
				SnapshotDate::newFromMWTimestamp( $data['dateStart'] ),
				SnapshotDate::newFromMWTimestamp( $data['dateEnd'] )
			);
		} else {
			return new static(
				SnapshotDate::newFromFormat( $data['dateStart'], $format ),
				SnapshotDate::newFromFormat( $data['dateEnd'], $format )
			);
		}
	}

	/**
	 * @param SnapshotDate $date
	 * @return static
	 */
	public static function newSingleDay( SnapshotDate $date ) {
		return new static(
			$date->getFloor(),
			$date->getCeiling()
		);
	}

	/**
	 * Get the dates for the week given dates is
	 * @return static
	 */
	public static function newLastWeek() {
		return new static(
			( new SnapshotDate( 'monday last week' ) )->getFloor(),
			( new SnapshotDate( 'sunday last week' ) )->getCeiling()
		);
	}

	/**
	 * Get the dates for the month given dates is
	 * @return static
	 */
	public static function newLastMonth() {
		return new static(
			( new SnapshotDate( 'first day of last month' ) )->getFloor(),
			( new SnapshotDate( 'last day of last month' ) )->getCeiling()
		);
	}

	/**
	 * Get the dates for the year given dates is
	 * @return static
	 */
	public static function newLastYear() {
		return new static(
			( new SnapshotDate( 'first day of january last year' ) )->getFloor(),
			( new SnapshotDate( 'last day of december last year' ) )->getCeiling()
		);
	}

	/**
	 * @param SnapshotDate $from
	 * @param SnapshotDate $to
	 */
	public function __construct( SnapshotDate $from, SnapshotDate $to ) {
		$this->from = $from;
		$this->to = $to;
	}

	/**
	 * @return SnapshotDate
	 */
	public function getFrom(): SnapshotDate {
		return $this->from->getFloor();
	}

	/**
	 * @return SnapshotDate
	 */
	public function getTo(): SnapshotDate {
		return $this->to->getCeiling();
	}
}
