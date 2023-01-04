<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\EnhancedUpload\ContentDroplets;

use MediaWiki\Extension\ContentDroplets\Droplet\TagDroplet;
use Message;
use RawMessage;

class AttachmentDroplet extends TagDroplet {

	/**
	 */
	public function __construct() {
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): Message {
		return new RawMessage( 'Attachments' );
	}

	/**
	 * @inheritDoc
	 */
	public function getDescription(): Message {
		return new RawMessage( "Area for organising attachments" );
	}

	/**
	 * @inheritDoc
	 */
	public function getIcon(): string {
		return 'articles';
	}

	/**
	 * @inheritDoc
	 */
	public function getRLModule(): string {
		return 'ext.enhancedupload.attachments.bootstrap';
	}

	/**
	 * @return array
	 */
	public function getCategories(): array {
		return [ 'content', 'lists' ];
	}

	/**
	 *
	 * @return string
	 */
	protected function getTagName(): string {
		return 'attachments';
	}

	/**
	 * @return array
	 */
	protected function getAttributes(): array {
		return [
			'title',
			'prefix',
			'categories',
			'description',
			'skipoptions',
			'hideversion',
			'hideeditor',
			'showsize',
			'showcategories'
		];
	}

	/**
	 * @return bool
	 */
	protected function hasContent(): bool {
		return true;
	}

	/**
	 * @return string|null
	 */
	public function getVeCommand(): ?string {
		return 'attachmentCommand';
	}
}
