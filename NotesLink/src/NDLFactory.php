<?php

namespace NotesLink;

class NDLFactory {

	/**
	 *
	 * @var \Config
	 */
	protected $config = null;

	/**
	 * @param \Config $config
	 * @return NDLFactory
	 */
	public function __construct( $config ) {
		$this->config = $config;
	}

	/**
	 * @param string $input
	 * @param \Title|null $title
	 * @return NDLParser|null
	 */
	public function newParserFromInput( $input, \Title $title = null ) {
		if ( !$title ) {
			$title = \RequestContext::getMain()->getTitle();
		}

		return new \NotesLink\NDLParser( $input, $this->config, $title );
	}

	/**
	 *
	 * @param NDLParser $parser
	 * @return NDLLinkRenderer
	 */
	public function getLinkRenderer( \NotesLink\NDLParser $parser ) {
		return new \NotesLink\NDLLinkRenderer( $parser, $this->config );
	}
}
