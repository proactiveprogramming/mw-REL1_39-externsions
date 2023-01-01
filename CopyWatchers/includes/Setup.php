<?php
/**
 *
 * @addtogroup Extensions
 * @author James Montalvo
 * @copyright © 2014 by James Montalvo
 * @licence GNU GPL v3+
 */

namespace CopyWatchers;

class Setup {

	/**
	* Handler for ParserFirstCallInit hook; sets up parser functions.
	* @see http://www.mediawiki.org/wiki/Manual:Hooks/ParserFirstCallInit
	* @param $parser Parser object
	* @return bool true in all cases
	*/
	static function setupParserFunctions ( &$parser ) {

		$copywatchers = new CopyWatchers( $parser );
		$copywatchers->setupParserFunction();

		// always return true
		return true;

	}

}
