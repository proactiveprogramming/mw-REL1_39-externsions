<?php

namespace MediaWiki\Extension\SimilarEditors;

interface Client {

	/**
	 * Retrieve the queried editor
	 *
	 * @param string $target
	 * @return Editor|null
	 */
	public function getEditor( string $target );

	/**
	 * Retrieve similar editors, or an error message key
	 *
	 * @param string $target
	 * @return Neighbor[]|string
	 */
	public function getSimilarEditors( string $target );
}
