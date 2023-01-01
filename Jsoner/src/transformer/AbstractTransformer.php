<?php

namespace jsoner\transformer;

/**
 * When implementing your own Transformer, you can use this
 * abstract class. Its very thin and just calls different methods
 * depending on the number of arguments. This is since json with
 * only on element might be displayed differently.
 *
 * Class AbstractTransformer
 * @package jsoner\transformer
 */
abstract class AbstractTransformer implements Transformer
{
	/**
	 * @var \jsoner\Config
	 */
	protected $config;

	public function __construct( $config ) {

		$this->config = $config;
	}

	public function transform( $json , $options ) {
		$numberOfElements = count( $json );

		if ( $numberOfElements === 1 ) {
			return $this->transformOne( $json, $options );
		}

		if ( $numberOfElements >= 1 ) {
			return $this->transformMultiple( $json, $options );
		}

		return $this->transformZero( $options );
	}

	abstract public function transformZero( $options );

	abstract public function transformOne( $json , $options );

	abstract public function transformMultiple( $json , $options );
}
