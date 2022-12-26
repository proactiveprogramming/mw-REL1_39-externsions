<?php

namespace BlueSpice\ExtendedStatistics\ContentDroplets;

use MediaWiki\Extension\ContentDroplets\Droplet\TagDroplet;
use Message;
use RawMessage;

class ProgressDroplet extends TagDroplet {

	/**
	 * @inheritDoc
	 */
	public function getName(): Message {
		return new RawMessage( 'Progress' );
	}

	/**
	 * @inheritDoc
	 */
	public function getDescription(): Message {
		return new RawMessage( "Progress description" );
	}

	/**
	 * @inheritDoc
	 */
	public function getIcon(): string {
		return 'ellipsis';
	}

	/**
	 * @inheritDoc
	 */
	public function getRLModule(): string {
		return 'ext.bluespice.extendedstatistics.visualEditorTagDefinition';
	}

	/**
	 * @return array
	 */
	public function getCategories(): array {
		return [ 'content', 'visualization', 'data' ];
	}

	/**
	 *
	 * @return string
	 */
	protected function getTagName(): string {
		return 'bs:statistics:progress';
	}

	/**
	 * @return array
	 */
	protected function getAttributes(): array {
		return [
			'basecount' => 200,
			'baseitem' => '',
			'progressitem' => 'OK',
			'width' => 150
		];
	}

	/**
	 * @return bool
	 */
	protected function hasContent(): bool {
		return false;
	}

	/**
	 * @return string|null
	 */
	public function getVeCommand(): ?string {
		return 'progressCommand';
	}

}
