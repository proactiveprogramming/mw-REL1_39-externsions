<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Domain;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

/**
 * What a {@link User} thought about the {@link Recording} of another user.
 * Stored in a {@link RecordingReview}.
 *
 * @since 0.1.0
 */
class RecordingReviewValue {
	public const NONE = 0;
	public const THUMB_UP = 1;
	public const THUMB_DOWN = 2;
	public const UNCERTAIN = 3;
	public const SKIPPED = 4;
}
