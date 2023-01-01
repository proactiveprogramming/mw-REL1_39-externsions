<?php

class AdvancedWikilink extends AdvancedLink {

	/**
	 * AdvancedWikilink constructor.
	 *
	 * @param Title $from
	 * @param Title $target
	 * @param Title|null $through
	 * @param bool $hidden
	 */
	public function __construct( Title $from, Title $target, Title $through = null, bool $hidden = false ) {
		parent::__construct( $from, $target, $through, $hidden );
	}

	/**
	 * Creates a new AdvancedWikilink object from a corresponding DB row (ab_links table).
	 * @param $dbRow
	 * @return AdvancedLink
	 */
	public static function newFromDBrow( $dbRow ) : ?AdvancedLink {
		$from = Title::newFromID( $dbRow->abl_from );

		//as it turns out, this is probably the only way to properly handle situations like [[Help:Help:Some page]]
		$temp = new stdClass();
		$temp->page_title = $dbRow->abl_title;
		$temp->page_namespace = $dbRow->abl_namespace;
		$target = Title::newFromRow( $temp );
		if ( !$from || !$target ) {
			return null;
		}
		$throughId = (int)($dbRow->abl_through ?: $dbRow->abl_hidden_through);

		return new self(
			$from,
			$target,
			$throughId === 0 ? null : Title::newFromID( $throughId ),
			$dbRow->abl_hidden_through != 0
		);
	}

	public function getTextForLogs() : string {
		return "wikilink from {$this->from->getArticleID()} to {$this->target->getPrefixedText()} " .
			"through {$this->getThroughID()} / {$this->getHiddenThroughID()}";
	}
}