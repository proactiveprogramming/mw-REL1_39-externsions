<?php

use MediaWiki\Extension\SimilarEditors\MockSimilarEditorsClient;
use MediaWiki\Extension\SimilarEditors\ResultsFormatterFactory;
use MediaWiki\Extension\SimilarEditors\SimilarEditorsClient;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;

// PHPUnit doesn't understand code coverage for code outside of classes/functions,
// like service wiring files. This *is* tested though, see
// tests/phpunit/integration/ServiceWiringTest.php
// @codeCoverageIgnoreStart

return [
	'SimilarEditorsClient' => static function ( MediaWikiServices $services ) {
		$config = $services->getMainConfig();
		$apiUrl = $config->get( 'SimilarEditorsApiUrl' );
		$apiUser = $config->get( 'SimilarEditorsApiUser' );
		$apiPassword = $config->get( 'SimilarEditorsApiPassword' );
		if (
			$apiUrl &&
			$apiUser &&
			$apiPassword
		) {
			return new SimilarEditorsClient(
				$services->getHttpRequestFactory(),
				LoggerFactory::getInstance( 'http' ),
				$apiUrl,
				$apiUser,
				$apiPassword
			);
		}
		return new MockSimilarEditorsClient();
	},
	'SimilarEditorsResultsFormatterFactory' => static function ( MediaWikiServices $services ) {
		return new ResultsFormatterFactory(
			$services->getUserFactory()
		);
	},
];

// @codeCoverageIgnoreEnd
