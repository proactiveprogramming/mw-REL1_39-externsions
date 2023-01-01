<?php

namespace BlueSpice\Expiry\RunJobsTriggerHandler;

use BlueSpice\Config;
use BlueSpice\Expiry\SpecialLogLogger;
use BlueSpice\RunJobsTriggerHandler\Interval\OnceADay;
use BlueSpice\UtilityFactory;
use MediaWiki\MediaWikiServices;
use MWException;
use MWStake\MediaWiki\Component\RunJobsTrigger\Handler;
use MWStake\MediaWiki\Component\RunJobsTrigger\Interval;
use Status;
use TitleFactory;
use Wikimedia\Rdbms\LoadBalancer;

class LogExpirations extends Handler {
	/** @var SpecialLogLogger */
	private $specialLogLogger;
	/** @var TitleFactory */
	private $titleFactory;
	/** @var UtilityFactory */
	private $utilityFactory;

	/**
	 * @param Config $config
	 * @param LoadBalancer $loadBalancer
	 * @return static
	 */
	public static function factory( $config, $loadBalancer ) {
		$services = MediaWikiServices::getInstance();

		// Unfortunately, RunJobsTriggerHandler does not yet
		// support full ObjectFactory specification
		return new static(
			$services->getService( 'BSExpirySpecialLogLogger' ),
			$services->getService( 'TitleFactory' ),
			$services->getService( 'BSUtilityFactory' ),
			$config,
			$loadBalancer
		);
	}

	/**
	 * @param SpecialLogLogger $logger
	 * @param TitleFactory $titleFactory
	 * @param UtilityFactory $utilityFactory
	 * @param Config $config
	 * @param LoadBalancer $loadBalancer
	 */
	public function __construct(
		SpecialLogLogger $logger, TitleFactory $titleFactory,
		UtilityFactory $utilityFactory, $config, $loadBalancer
	) {
		parent::__construct( $config, $loadBalancer );

		$this->specialLogLogger = $logger;
		$this->titleFactory = $titleFactory;
		$this->utilityFactory = $utilityFactory;
	}

	/**
	 * @return Status
	 * @throws MWException
	 */
	protected function doRun() {
		$db = $this->loadBalancer->getConnection( DB_REPLICA );
		$expirations = $db->select(
			[ 'p' => 'page', 'exp' => 'bs_expiry' ],
			[ 'p.page_title', 'p.page_namespace', 'exp.exp_comment as comment' ],
			[ 'exp_date = CURDATE()' ],
			__METHOD__,
			[],
			[
				'p' => [
					'INNER JOIN', 'page_id = exp_page_id'
				]
			]
		);

		foreach ( $expirations as $expiration ) {
			$this->specialLogLogger->log(
				$this->utilityFactory->getMaintenanceUser()->getUser(),
				$this->titleFactory->newFromRow( $expiration ),
				SpecialLogLogger::LOG_ACTION_EXPIRED,
				$expiration->comment || ''
			);
		}

		return Status::newGood();
	}

	/**
	 * @return Interval
	 */
	public function getInterval() {
		return new OnceADay();
	}
}
