<?php
namespace BS\UsageTracker\Collectors;

use BS\UsageTracker\Jobs\UsageTrackerCollectJob;
use MediaWiki\MediaWikiServices;

abstract class Base {
	protected $identifier = 'bs:';
	protected $descKey = 'bs-usagetracker-base-collector-desc';

	/**
	 * Initial configuration. Needed to register as job
	 * @var array
	 */
	protected $config = [];

	/**
	 *
	 * @param array $config
	 */
	public function __construct( $config = [] ) {
		if ( isset( $config['config'] ) && is_array( $config['config'] ) ) {
			if ( isset( $config['config']['identifier'] ) ) {
				$this->identifier = $config['config']['identifier'];
			}
		}
		$this->config = $config;
	}

	/**
	 *
	 * @return string
	 */
	public function getDescriptionKey() {
		return $this->descKey;
	}

	/**
	 *
	 * @return string
	 */
	public function getIdentifier() {
		return $this->identifier;
	}

	/**
	 *
	 * @return \BS\UsageTracker\CollectorResult
	 */
	abstract public function getUsageData();

	/**
	 *
	 * @return bool
	 */
	public function registerJob() {
		$oJob = new UsageTrackerCollectJob(
			\Title::newFromText( $this->identifier . wfTimestampNow() ),
			$this->config
		);
		MediaWikiServices::getInstance()->getJobQueueGroup()->push( $oJob );
		return true;
	}
}
