<?php

namespace BlueSpice\HideTitle;

interface ICssSelectorModule {

	/**
	 * Example:
	 * return [
	 *	'all' => [
	 *		'#firstHeading'
	 *	]
	 * ];
	 *
	 * @return array
	 */
	public function getSelectors(): array;

	/**
	 * @param ResourceLoaderContext $context
	 * @return bool
	 */
	public function skip( $context ): bool;
}
