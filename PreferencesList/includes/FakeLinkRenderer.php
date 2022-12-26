<?php

use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Linker\LinkTarget;

/**
 * Class FakeLinkRenderer
 */
class FakeLinkRenderer extends LinkRenderer {

	/**
	 * @param LinkTarget $target
	 * @param string|null $text
	 * @param array $extraAttribs
	 * @param array $query
	 *
	 * @return string|null
	 */
	public function makeLink( $target, $text = null, array $extraAttribs = [], array $query = [] ) {
		return $text;
	}

}
