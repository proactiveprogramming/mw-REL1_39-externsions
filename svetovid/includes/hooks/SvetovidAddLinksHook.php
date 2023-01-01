<?php

interface SvetovidAddLinksHook {
	/**
	 * @param Title $targetTitle title of the page to link to
	 * @param WikiPage $page page to process
	 * @param string[] $texts array of possible link texts
	 * @param string $text page's wikitext (to be modified by the hook)
	 * @param int $changes number of changes on the page (to be modified by the hook)
	 *
	 * @return bool
	 */
	public function onSvetovidAddLinks(
		Title $targetTitle,
		WikiPage $page,
		array $texts,
		string &$text,
		int &$changes
	) : bool;
}