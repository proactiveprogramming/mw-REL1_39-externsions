<?php

namespace MediaWiki\Extension\PageCheckout\Activity;

use MediaWiki\Extension\PageCheckout\CheckoutManager;
use MediaWiki\Extension\Workflows\Activity\ActivityPropertyValidator;
use MediaWiki\Extension\Workflows\Activity\ExecutionStatus;
use MediaWiki\Extension\Workflows\Activity\GenericActivity;
use MediaWiki\Extension\Workflows\Definition\ITask;
use MediaWiki\Extension\Workflows\Exception\WorkflowExecutionException;
use MediaWiki\Extension\Workflows\WorkflowContext;
use MediaWiki\User\UserFactory;
use Title;
use TitleFactory;
use User;

abstract class CheckoutActivity extends GenericActivity {
	/** @var CheckoutManager */
	protected $manager;

	/** @var UserFactory */
	protected $userFactory;
	/** @var TitleFactory */
	protected $titleFactory;
	/** @var WorkflowContext */
	protected $workflowContext;

	/**
	 * CheckoutActivity constructor.
	 * @param CheckoutManager $checkoutManager
	 * @param UserFactory $userFactory
	 * @param TitleFactory $titleFactory
	 * @param ITask $task
	 */
	public function __construct(
			CheckoutManager $checkoutManager,
			UserFactory $userFactory,
			TitleFactory $titleFactory,
			ITask $task
		) {
		parent::__construct( $task );
		$this->manager = $checkoutManager;
		$this->userFactory = $userFactory;
		$this->titleFactory = $titleFactory;
	}

	/**
	 * @inheritDoc
	 */
	public function execute( $data, WorkflowContext $context ): ExecutionStatus {
		if ( !isset( $data['user'] ) ) {
			throw new WorkflowExecutionException(
				'Property \"user\" must be set', $this->getTask()
			);
		}
		if ( $data['user'] === 'Mediawiki default' ) {
			// UserFactory does not have "newSystemUser" method :(
			$user = User::newSystemUser( $data['user'] );
		} else {
			$user = $this->userFactory->newFromName( $data['user'] );
		}
		if ( !$user instanceof User || ( !$user->isRegistered() && !$user->isSystemUser() ) ) {
			throw new WorkflowExecutionException(
				'Property \"user\" must be set to a valid user', $this->getTask()
			);
		}
		$title = $this->getAffectedTitle( $data, $context );
		if ( !$title instanceof Title ) {
			throw new WorkflowExecutionException(
				'No valid title can be constructed from params', $this->getTask()
			);
		}
		$this->workflowContext = $context;
		try {
			$this->doAction( $user, $title );
		} catch ( \Exception $ex ) {
			throw new WorkflowExecutionException(
				$ex->getMessage(), $this->getTask()
			);
		}

		return new ExecutionStatus( static::STATUS_COMPLETE, $data );
	}

	/**
	 * @param array $data
	 * @param WorkflowContext $context
	 * @return Title|null
	 */
	private function getAffectedTitle( $data, WorkflowContext $context ) {
		if ( isset( $data['pageId'] ) ) {
			return $this->titleFactory->newFromID( $data['pageId'] );
		}
		if ( isset( $data['pagename'] ) ) {
			return $this->titleFactory->newFromText( $data['pagename'] );
		}

		return $context->getContextPage();
	}

	/**
	 * @param User $user
	 * @param Title $title
	 */
	abstract protected function doAction( User $user, Title $title );

	/**
	 * @return array[]|null
	 */
	public function getPropertySpecification(): ?array {
		return [
			'pageId' => [
				ActivityPropertyValidator::TYPE => ActivityPropertyValidator::TYPE_INT,
				ActivityPropertyValidator::REQUIRED => false
			],
			'pagename' => [
				ActivityPropertyValidator::TYPE => ActivityPropertyValidator::TYPE_STRING,
				ActivityPropertyValidator::REQUIRED => false
			],
			'user' => [
				ActivityPropertyValidator::TYPE => ActivityPropertyValidator::TYPE_STRING,
				ActivityPropertyValidator::REQUIRED => true
			]
		];
	}
}
