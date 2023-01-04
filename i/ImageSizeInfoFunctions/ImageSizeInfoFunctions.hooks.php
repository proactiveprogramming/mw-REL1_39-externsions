<?php
/**
 * ImageSizeInfoFunctions
 * ImageSizeInfoFunctions Hooks
 *
 * @license		GNU GPL v2.0
 * @package		ImageSizeInfoFunctions
 * @link		https://github.com/CurseStaff/ImageSizeInfoFunctions
 *
 **/
class ImageSizeInfoFunctionsHooks {
	/**
	 * Sets up this extension's parser functions.
	 *
	 * @access	public
	 * @param	object	Parser object passed as a reference.
	 * @return	boolean	true
	 */
	static public function onParserFirstCallInit( Parser &$parser ) {
		$parser->setFunctionHook( "imgw", "ImageSizeInfoFunctionsHooks::getImageWidth");
		$parser->setFunctionHook( "imgh", "ImageSizeInfoFunctionsHooks::getImageHeight");

		return true;
	}

	/**
	 * Function for when the parser object is being cleared.
	 * @see	https://www.mediawiki.org/wiki/Manual:Hooks/ParserClearState
	 *
	 * @param $parser
	 * @return bool
	 */
	static public function onParserClearState( &$parser ) {
		return true;
	}

	/**
	 * Function to get the width of the image.
	 *
	 * @param	$parser	Parser object passed a reference
	 * @param	string	Name of the image being parsed in
	 * @return	mixed	integer of the width or error message.
	 */
	static public function getImageWidth( &$parser, $image = '' ) {
		if ( !$parser->incrementExpensiveFunctionCount() ) {
			return wfMessage( 'error_returning_width' )->text();
		}
		try {
			$title = Title::newFromText( $image, NS_FILE );
			$file = wfFindFile( $title );
			$width = ( is_object( $file ) && $file->exists() ) ? $file->getWidth() : 0;
			return $width;
		} catch ( Exception $e ) {
			return wfMessage( 'error_returning_width' )->text();
		}
	}

	/**
	 * Function to get the height of the image.
	 *
	 * @param	$parser	Parser object passed a reference
	 * @param	string	Name of the image being parsed in
	 * @return	mixed	integer of the height or error message.
	 */
	static public function getImageHeight( &$parser, $image = '' ) {
		if ( !$parser->incrementExpensiveFunctionCount() ) {
			return wfMessage( 'error_returning_height' )->text();
		}
		try {
			$title = Title::newFromText( $image, NS_FILE );
			$file = wfFindFile( $title );
			$height = ( is_object( $file ) && $file->exists() ) ? $file->getHeight() : 0;
			return $height;
		} catch ( Exception $e ) {
			return wfMessage( 'error_returning_height' )->text();
		}
	}
}
