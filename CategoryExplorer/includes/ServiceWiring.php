<?php
use MediaWiki\MediaWikiServices;

return [
	'CategoryExplorer.Config' => static function ( MediaWikiServices $services ) {
		return $services->getService( 'ConfigFactory' )
				->makeConfig( categoryexplorer );
	},
];
