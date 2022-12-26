<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\TexVC\Nodes;

use MediaWiki\Extension\Math\TexVC\MMLmappings\BaseMethods;

class Fun2 extends TexNode {

	/** @var string */
	protected $fname;
	/** @var TexNode */
	protected $arg1;
	/** @var TexNode */
	protected $arg2;

	public function __construct( string $fname, TexNode $arg1, TexNode $arg2 ) {
		parent::__construct( $fname, $arg1, $arg2 );
		$this->fname = $fname;
		$this->arg1 = $arg1;
		$this->arg2 = $arg2;
	}

	/**
	 * @return string
	 */
	public function getFname(): string {
		return $this->fname;
	}

	/**
	 * @return TexNode
	 */
	public function getArg1(): TexNode {
		return $this->arg1;
	}

	/**
	 * @return TexNode
	 */
	public function getArg2(): TexNode {
		return $this->arg2;
	}

	public function inCurlies() {
		return $this->render();
	}

	public function render() {
		return '{' . $this->fname . ' ' . $this->arg1->inCurlies() . $this->arg2->inCurlies() . '}';
	}

	public function renderMML( $arguments = [] ) {
		$bm = new BaseMethods();
		$res = $bm->checkAndParse( $this->fname, $this, $arguments, null );
		if ( $res ) {
			return $res;
		} else {
			return "NotImplemented Fun2 for: " . $this->fname;
		}
	}

	public function extractIdentifiers( $args = null ) {
		if ( $args == null ) {
			$args = [ $this->arg1, $this->arg2 ];
		}
		return parent::extractIdentifiers( $args );
	}

	public function name() {
		return 'FUN2';
	}
}
