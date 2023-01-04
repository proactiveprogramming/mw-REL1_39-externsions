<?php

namespace BlueSpice\HideTitle;

class DefaultCssSelectorModule implements ICssSelectorModule {

	/**
	 * @return array
	 */
	public function getSelectors(): array {
		return [
			'all' => [
				'#firstHeading',
				'#content h1.firstHeading',
				'#contentSub',
				'#siteNotice'
			]
		];
	}

	/**
	 * @param ResourceLoaderContext $context
	 * @return bool
	 */
	public function skip( $context ): bool {
		return false;
	}
}
