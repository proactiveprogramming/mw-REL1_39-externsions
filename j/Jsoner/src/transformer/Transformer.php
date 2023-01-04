<?php

namespace jsoner\transformer;

/**
 * When implementing your own Transformer, use this interface
 * to add your super-custom transformer.
 * Interface Transformer
 * @package jsoner\transformer
 */
interface Transformer
{
	public function transform( $json, $options );
}
