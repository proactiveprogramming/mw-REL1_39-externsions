<?php

class ReplaceSetHooks {
	/**
	 * @param Parser $parser
	 */
	public static function efReplaceSetRegisterParser( $parser ) {
		$parser->setFunctionHook( 'replaceset', [ 'ReplaceSet', 'parserFunctionObj' ], Parser::SFH_OBJECT_ARGS );
	}
}
