<?php
/*
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 */

namespace MediaWiki\Extension\CommentStreams;

use EchoEventPresentationModel;
use Message;

class EchoCSPresentationModel extends EchoEventPresentationModel {
	/**
	 * @inheritDoc
	 */
	public function getIconType(): string {
		return 'chat';
	}

	/**
	 * @inheritDoc
	 */
	public function getPrimaryLink(): array {
		$id = $this->event->getExtraParam( 'comment_id' );
		return [
			'url' => $this->event->getTitle()->getFullURL() . '#cs-comment-' . $id,
			'label' => $this->msg( "notification-link-label-{$this->type}" )
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getHeaderMessage(): Message {
		$msg = wfMessage( "notification-header-{$this->type}" );
		$this->addMessageParams( $msg );
		return $msg;
	}

	/**
	 * @inheritDoc
	 */
	public function getBodyMessage() {
		$msg = wfMessage( "notification-body-{$this->type}" );
		$this->addMessageParams( $msg );
		return $msg;
	}

	/**
	 * @param Message $msg
	 */
	private function addMessageParams( Message $msg ) {
		$msg->params( $this->event->getExtraParam( 'comment_author_display_name' ) );
		$msg->params( $this->event->getExtraParam( 'comment_title' ) );
		$msg->params( $this->event->getExtraParam( 'associated_page_display_title' ) );
		$msg->params( $this->event->getExtraParam( 'comment_author_username' ) );
		$msg->params( $this->event->getExtraParam( 'comment_wikitext' ) );
		$msg->params( $this->getViewingUserForGender() );
	}

	/**
	 * @inheritDoc
	 */
	public function canRender(): bool {
		return $this->event->getTitle() !== null;
	}
}
