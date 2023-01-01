<?php

namespace jsoner;

use jsoner\exceptions\TransformerException;
use jsoner\transformer\Transformer;

class TransformerRegistry
{
	private $config;

	private $transformers = [
		't-JsonDump' => '\\jsoner\\transformer\\JsonDumpTransformer',
		't-SingleElement' => '\\jsoner\\transformer\\SingleElementTransformer',
		't-WikitextTable' => '\\jsoner\\transformer\\WikitextTableTransformer',
		't-InlineList' => '\\jsoner\\transformer\\InlineListTransformer',
		't-StackedElement' => '\\jsoner\\transformer\\StackedElementTransformer',
    't-mwTemplate' => '\\jsoner\\transformer\\MediaWikiTemplateTransformer',
    't-mwTemplateAnonymous' => '\\jsoner\\transformer\\MediaWikiTemplateTransformerAnonymous',    
	];

	public function __construct( $config ) {
		$this->config = $config;
	}

	public function addTransformer( $key, $fqcn ) {
		$this->transformers[$key] = $fqcn;
	}

	/**
	 * @param $key
	 * @return Transformer
	 */
	public function getTransformerByKey( $key ) {
		$transformerClass = Helper::getArrayValueOrDefault( $this->transformers, $key );

		if ( $transformerClass !== null ) {
			return new $transformerClass( $this->config );
		}

		throw new TransformerException( "No such transformer: $key" );
	}
}
