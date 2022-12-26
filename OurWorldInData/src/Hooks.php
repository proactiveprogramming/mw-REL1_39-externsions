<?php

namespace MediaWiki\Extension\OurWorldInData;

use Config;
use Html;
use MediaWiki\Hook\ParserFirstCallInitHook;
use MWException;
use Parser;
use PPFrame;

class Hooks implements ParserFirstCallInitHook {
	/** @var Config Main config */
	private Config $config;

	/**
	 * @param Config $config Main config
	 */
	public function __construct( Config $config ) {
		$this->config = $config;
	}

	/**
	 * Register the &lt;ourworldindata&gt; tag
	 *
	 * @param Parser $parser Parser
	 * @throws MWException On error
	 */
	public function onParserFirstCallInit( $parser ) {
		$parser->setHook( 'ourworldindata', [ $this, 'renderOurWorldInData' ] );
	}

	/**
	 * Handler for the &lt;ourworldindata&gt; tag
	 *
	 * @param string $input Dataset to embed
	 * @param array $args Arguments passed to dataset
	 * @param Parser $parser Parser
	 * @param PPFrame $frame Parser Frame
	 * @return string|array HTML that will not be processed further
	 */
	public function renderOurWorldInData( string $input, array $args, Parser $parser, PPFrame $frame ) {
		// validate $input a bit
		if ( !preg_match( ',^[a-z0-9_-]+$,i', $input ) ) {
			return '<strong class="error">'
				. htmlspecialchars( wfMessage( 'owid-error-key' )->text() )
				. '</strong>';
		}

		$urlPattern = $this->config->get( 'OurWorldInDataUrl' );
		$baseUrl = str_replace( '$1', rawurlencode( $input ), $urlPattern );
		$url = wfAppendQuery( $baseUrl, $args );
		$parser->getOutput()->addModuleStyles( [ 'ext.owid' ] );
		return [
			Html::element(
				'iframe',
				[
					'src' => $url,
					'loading' => 'lazy',
					'class' => 'owid-frame'
				]
			),
			'markerType' => 'nowiki'
		];
	}
}
