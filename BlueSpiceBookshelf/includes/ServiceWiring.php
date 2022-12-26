<?php

use BlueSpice\Bookshelf\TreeParser;
use BlueSpice\ExtensionAttributeBasedRegistry;
use MediaWiki\MediaWikiServices;

return [
	'BSBookshelfTreeParser' => static function ( MediaWikiServices $services ) {
		$lineParsers = new ExtensionAttributeBasedRegistry( 'BlueSpiceBookshelfLineProcessors' );
		return new TreeParser(
			$services->getConfigFactory()->makeConfig( 'bsg' ),
			$lineParsers
		);
	},
	'BSBookshelfPageHierarchyProviderFactory' => static function ( MediaWikiServices $services ) {
		return new \BlueSpice\Bookshelf\PageHierarchyProviderFactory();
	},
];
