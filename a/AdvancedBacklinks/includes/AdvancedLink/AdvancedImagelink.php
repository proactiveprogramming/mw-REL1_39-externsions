<?php

class AdvancedImagelink extends AdvancedLink {

	/**
	 * AdvancedImagelink constructor.
	 *
	 * @param Title $from
	 * @param Title $target
	 * @param Title|null $through
	 */
	public function __construct( Title $from, Title $target, Title $through = null ) {
		parent::__construct( $from, $target, $through );
	}

	/**
	 * Creates a new AdvancedImageink object from a corresponding DB row (ab_images table).
	 * @param $dbRow
	 * @return AdvancedLink
	 */
	public static function newFromDBrow( $dbRow ) : ?AdvancedLink {
		$from = Title::newFromID( $dbRow->abi_from );

		//as it turns out, this is probably the only way to properly handle situations like [[File:File:Something.jpg]]
		$temp = new stdClass();
		$temp->page_title = $dbRow->abi_title;
		$temp->page_namespace = NS_FILE;
		$target = Title::newFromRow( $temp );
		if ( !$from || !$target ) {
			return null;
		}
		return new AdvancedImagelink(
			$from,
			$target,
			$dbRow->abi_through == 0 ? null : Title::newFromID( $dbRow->abi_through )
		);
	}

	public function getTextForLogs() : string {
		return "imagelink from {$this->from->getArticleID()} to {$this->target->getPrefixedText()} " .
			"through {$this->getThroughID()} / {$this->getHiddenThroughID()}";
	}
}