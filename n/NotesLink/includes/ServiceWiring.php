<?php

use MediaWiki\MediaWikiServices;

// PHP unit does not understand code coverage for this file
// as the @covers annotation cannot cover a specific file
// This is fully tested in ServiceWiringTest.php
// @codeCoverageIgnoreStart

return [

	'NotesLinkNDLFactory' => static function ( MediaWikiServices $services ) {
		return new \NotesLink\NDLFactory(
			$services->getConfigFactory()->makeConfig( 'noteslink' )
		);
	},

];

// @codeCoverageIgnoreEnd
