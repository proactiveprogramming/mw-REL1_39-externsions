<?php
declare( strict_types=1 );

namespace MediaWiki\Extension\WikibaseStatementUpdater;

use ApiBase;
use ApiMain;
use MediaWiki\Extension\WikibaseStatementUpdater\Batch\BatchListStore;
use MediaWiki\Extension\WikibaseStatementUpdater\Batch\BatchStore;
use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\Rdbms\ILoadBalancer;
use const DB_REPLICA;

/**
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 */
class WikibaseStatementUpdaterActionApi extends ApiBase {
	/** @var ILoadBalancer */
	private $loadBalancer;

	public function __construct( ApiMain $mainModule, $moduleName, ILoadBalancer $loadBalancer ) {
		parent::__construct( $mainModule, $moduleName );
		$this->loadBalancer = $loadBalancer;
	}

	/** @inheritDoc */
	public function execute() {
		$id = $this->getParameter( 'batch' );

		$db = $this->loadBalancer->getConnectionRef( DB_REPLICA );
		$batchListStore = new BatchListStore( $db );
		$list = $batchListStore->get( $id );

		if ( !$list ) {
			$this->dieWithError( [ 'apierror-badparameter', 'batch' ] );
		}

		$batchStore = new BatchStore( $db );
		$items = $batchStore->getRecords( $list );

		$okCount = $errorCount = $incompleteCount = 0;
		$count = count( $items );

		foreach ( $items as $item ) {
			$status = $item->getOutput()['status'] ?? 'unknown';
			if ( $status === 'ok' ) {
				$okCount++;
			} elseif ( $status === 'error' ) {
				$errorCount++;
			} else {
				$incompleteCount++;
			}
		}

		$result = $this->getResult();
		$result->addValue(
			null,
			$this->getModuleName(),
			[
				'status' => $list->getStatus(),
				'count' => $count,
				'ok' => $okCount,
				'error' => $errorCount,
				'incomplete' => $incompleteCount,
			]
		);
	}

	public function needsToken() {
		return 'csrf';
	}

	protected function getAllowedParams() {
		return [
			'batch' => [
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => true,
			],
		];
	}
}
