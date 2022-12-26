<?php
declare( strict_types=1 );

namespace MediaWiki\Extension\WikibaseStatementUpdater\Updater;

use GenericParameterJob;
use Job;
use MediaWiki\Extension\WikibaseStatementUpdater\Services;

/**
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 */
class UpdateJob extends Job implements GenericParameterJob {
	public function __construct( $params = null ) {
		parent::__construct( 'WSUUpdateJob', $params );
		$this->removeDuplicates = true;
	}

	public static function newJob( int $batchItemId, int $batchListId ): self {
		return new self(
			[
				'batchItemId' => $batchItemId,
				'batchListId' => $batchListId,
			]
		);
	}

	public function run(): bool {
		$updateManager = Services::getInstance()->getUpdateManager();
		$updateManager->process( $this->params['batchItemId'], $this->params['batchListId'] );
		return true;
	}
}
