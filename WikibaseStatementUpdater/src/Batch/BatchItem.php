<?php
declare( strict_types=1 );

namespace MediaWiki\Extension\WikibaseStatementUpdater\Batch;

/**
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 */
class BatchItem {
	/** @var string */
	private $subject;
	/** @var string */
	private $command;
	/** @var string */
	private $commandId;
	/** @var string */
	private $value;

	public function __construct(
		string $subject,
		string $command,
		string $commandId,
		string $value
	) {
		$this->subject = $subject;
		$this->command = $command;
		$this->commandId = $commandId;
		$this->value = $value;
	}

	public function getSubject(): string {
		return $this->subject;
	}

	public function getCommand(): string {
		return $this->command;
	}

	public function getCommandId(): string {
		return $this->commandId;
	}

	public function getValue(): string {
		return $this->value;
	}

	public function getSpec(): array {
		return [
			'class' => __CLASS__,
			'args' => [
				$this->subject,
				$this->command,
				$this->commandId,
				$this->value,
			],
		];
	}
}
