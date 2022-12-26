<?php
/**
 * Hooks for LogEntry extension
 *
 * @file
 * @ingroup Extensions
 */

class LogEntryHooks {

	/**
	 * @param Parser $parser
	 */
	public static function register( $parser ) {
		$parser->setHook( 'logentry', [ __CLASS__, 'render' ] );
	}

	/**
	 * @param string $input
	 * @param array $args
	 * @param Parser $parser
	 * @return string
	 */
	public static function render( $input, $args, $parser ) {
		global $egLogEntryMultiLine, $egLogEntryMultiLineRows;

		// Don't cache since we are passing the token in the form
		$parser->getOutput()->updateCacheExpiry( 0 );
		if ( method_exists( $parser, 'getUserIdentity' ) ) {
			// MW 1.36+
			$user = MediaWiki\MediaWikiServices::getInstance()
				->getUserFactory()->newFromUserIdentity( $parser->getUserIdentity() );
		} else {
			$user = $parser->getUser();
		}

		$htmlResult = Xml::openElement( 'form',
			[
				'id' => 'logentryform',
				'name' => 'logentryform',
				'method' => 'post',
				'action' => htmlspecialchars( SpecialPage::getTitleFor( 'LogEntry' )->getLocalURL() ),
				'enctype' => 'multipart/form-data'
			]
		);
		if ( $egLogEntryMultiLine ) {
			$htmlResult .= Xml::element( 'textarea',
				[
					'rows' => $egLogEntryMultiLineRows,
					'name' => 'line',
					'style' => 'width:100%;'
				]
			);
			$htmlResult .= Xml::tags( 'div',
				[
					'align' => 'right'
				],
				Xml::element( 'input',
					[
						'type' => 'submit',
						'name' => 'append',
						'value' => wfMessage( 'logentry-append' )->text()
					]
				)
			);
		} else {
			$htmlResult .= Xml::element( 'input',
				[
					'type' => 'text',
					'name' => 'line',
					'style' => 'width:80%;'
				]
			);
			$htmlResult .= Xml::element( 'input',
				[
					'type' => 'submit',
					'name' => 'append',
					'value' => wfMessage( 'logentry-append' )->text()
				]
			);
		}
		$htmlResult .= Xml::element( 'input',
			[
				'type' => 'hidden',
				'name' => 'page',
				'value' => $parser->getTitle()->getPrefixedText()
			]
		);
		$htmlResult .= Xml::element( 'input',
			[
				'type' => 'hidden',
				'name' => 'token',
				'value' => $user->getEditToken()
			]
		);
		$htmlResult .= Xml::closeElement( 'form' );

		return $htmlResult;
	}
}
