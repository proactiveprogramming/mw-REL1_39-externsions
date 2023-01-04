<?php

namespace jsoner\transformer;

class JsonDumpTransformer extends AbstractTransformer
{
	public function transformZero( $options ) {
		$emptyJsonObject = "{}";
		$this->transformMultiple( $emptyJsonObject, $options );
	}

	public function transformOne( $json , $options ) {
		return $this->transformMultiple( $json, $options );
	}

	public function transformMultiple( $json , $options ) {
		$json_encode_options = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
		return "<pre>" . json_encode( $json, $json_encode_options ) . "</pre>";
	}
}
