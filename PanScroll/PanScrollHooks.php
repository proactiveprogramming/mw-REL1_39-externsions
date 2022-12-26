<?php

class PanScrollHooks {

	/**
	 * @param Parser &$parser
	 */
	public static function init( Parser &$parser ) {
		$parser->setHook( 'panscroll', 'PanScrollHooks::renderFromTag' );
	}

	/**
	 * @param string $input
	 * @param array $args
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @return string|bool
	 */
	public static function renderFromTag( $input, array $args, Parser $parser, PPFrame $frame ) {
		$parser->getOutput()->addModules( [ 'ext.panscroll.core' ] );
		$parsedInput = $parser->internalParse( $input );
		$blockSize = self::getConfigArgs( $args );

		$output = <<< EOT
<div id="panscroll-element" class="panscroll-element" style="width: %s; height: %s">
<div class="panscroll-container">
<div class="panscroll-content">%s</div>
</div>
</div>
EOT;
		$output = sprintf( $output, $blockSize['width'], $blockSize['height'], $parsedInput );
		return [ $output, 'noparse' => false, 'isHTML' => true ];
	}

	/**
	 * @param array $args
	 * @return array
	 */
	private static function getConfigArgs( array $args ) {
		$size = [ 'width' => '100%', 'height' => '100%' ];
		if ( array_key_exists( 'width', $args ) ) {
			$size['width'] = $args['width'];
		}
		if ( array_key_exists( 'height', $args ) ) {
			$size['height'] = $args['height'];
		}
		return $size;
	}
}
