<?php

namespace MediaWiki\TimedMediaHandler\TimedText\DOM;

/**
 * WebVTT Language object, maps roughly to an HTML span with lang attribute.
 */
class LanguageNode extends InternalNode {
	/**
	 * @param string $lang
	 */
	public function __construct( $lang ) {
		$this->annotation = $lang;
	}

	/**
	 * @return string
	 */
	public function getLang() {
		return $this->annotation;
	}
}
