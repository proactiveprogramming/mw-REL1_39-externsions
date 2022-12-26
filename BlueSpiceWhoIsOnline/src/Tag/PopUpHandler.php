<?php

namespace BlueSpice\WhoIsOnline\Tag;

use BlueSpice\Renderer\Params;
use BlueSpice\Tag\Handler;
use BlueSpice\WhoIsOnline\Renderer\UserList;
use BlueSpice\WhoIsOnline\Tracer;
use Config;
use Html;
use MediaWiki\MediaWikiServices;
use Parser;
use PPFrame;
use RequestContext;

class PopUpHandler extends Handler {

	/**
	 * @var int
	 */
	protected static $idCounter = 0;

	/**
	 * @var Tracer
	 */
	protected $tracer = null;

	/**
	 * @param string $processedInput
	 * @param array $processedArgs
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param Tracer|null $tracer
	 */
	public function __construct( $processedInput, array $processedArgs,
		Parser $parser, PPFrame $frame, Tracer $tracer = null ) {
		parent::__construct( $processedInput, $processedArgs, $parser, $frame );
		if ( !$tracer ) {
			$tracer = MediaWikiServices::getInstance()->getService( 'BSWhoIsOnlineTracer' );
		}
		$this->tracer = $tracer;
	}

	/**
	 * @return string
	 */
	public function handle() {
		$recordSet = $this->tracer->getTracedRecords();

		$this->parser->getOutput()->setPageProperty( 'bs-tag-userscount', 1 );
		$targetId = $this->getTargetId();
		$portlet = MediaWikiServices::getInstance()->getService( 'BSRendererFactory' )->get(
			'whoisonline-userlist',
			new Params( [
				UserList::PARAM_RECORD_SET => $recordSet,
				'target' => $targetId
			] ),
			RequestContext::getMain()
		);

		$anchor = Html::element( 'a', [
				'class' => 'wo-link',
				'title' => wfMessage( 'bs-whoisonline-widget-title' ),
				'data-target-id' => $targetId,
				'data-target' => $portlet->render()
			],
			( empty( $this->processedArgs[PopUp::PARAM_ANCHOR_TEXT] )
				? wfMessage( 'bs-whoisonline-widget-title' )
				: $this->processedArgs[PopUp::PARAM_ANCHOR_TEXT] )
		);

		return $anchor;
	}

	/**
	 * @return string
	 */
	protected function getTargetId() {
		return 'bs-wo-link-' . static::$idCounter++;
	}

	/**
	 * @return Config
	 */
	protected function getConfig() {
		return MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'bsg' );
	}
}
