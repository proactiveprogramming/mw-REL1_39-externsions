<?php
/**
 * Character Escapes
 * Character Escapes Hooks
 *
 * @author		David M. Sledge
 * @package		Character Escapes
 * @license		GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link		http://www.mediawiki.org/wiki/Extension:Character_Escapes
 *
 **/

class CharacterEscapesHooks {
	/**
	 * Available Parser Hook Tags
	 *
	 * @var		array
	 */
	public static $tags = [
		"esc"		=> "charEsc",
		"unesc"		=> "charUnesc"
	];

	/**
	 * Sets up this extensions parser functions.
	 *
	 * @access	public
	 * @param	object	Parser object passed as a reference.
	 * @return	boolean	true
	 */
	static public function onParserFirstCallInit(Parser &$parser) {
		foreach (self::$tags as $hook => $method) {
			$parser->setHook($hook, array(
				__CLASS__,
				$method
			));
		}
		return true;
	}

	public static function unstrip($input, $args, $parser) {
		$regex = "/\x07UNIQ[0-9a-fA-F]{1,16}-(" . implode('|', array_keys(self::$tags)) . ")-[0-9a-fA-F]{8}-QINU\x07/";

		// find all the unique identifiers for the esc tag
		preg_match_all($regex, $input, $strippedTags);
		$unstrippedTags = array();

		// unstrip each unique identifier
		foreach ($strippedTags[0] as $strippedTag)
			$unstrippedTags[$strippedTag] = $parser->mStripState->unstripGeneral($strippedTag);

		// replace each unique identifier with the unstripped text
		$input = strtr($input, $unstrippedTags);

		return $input;
	}

	public static function charEsc($input, $args, $parser) {
		$input = self::unstrip($input, $args, $parser);

		return self::escChars($input);
	}

	// these character escapes are so the nested parser functions and tags are
	// not called before the loops are performed.  Many thanks to Gero Scholz
	// (a Dynamic Page List 2 author) for the basic idea. This implementation
	// is different in that it uses a convention similar to what is seen in
	// many programming languages.
	public static function escChars($text) {
		// The following character escape sequences are used to avoid
		// premature tag expansion and parser function execution.
		//
		// character sequence  escape sequence
		//		   {{				 \o
		//		   }}				 \c
		//		   |				 \p
		//		   <				 \l
		//		   >				 \g
		//		 newline			 \n
		//		   \				 \\
		// prefix the pre-existing escape sequences with backslashes
		$text = str_replace(array(
			"\\",
			"{{",
			"}}",
			"|",
			"<",
			">",
			"\n"
		), array(
			"\\\\",
			"\\o",
			"\c",
			"\\p",
			"\\l",
			"\\g",
			"\\n"
		), $text);

		return $text;
	}

	public static function charUnesc($input, $args, $parser) {
		$input = self::unstrip($input, $args, $parser);

		return self::unescChars($input);
	}

	public static function unescChars($text) {
		// since we're dealing with regular expressions and strings,
		// the backslash character must be double escaped (4:1 ratio)
		$text = preg_replace(array(
			"/(?<!\\\\)((\\\\\\\\)*)\\\\o/",
			"/(?<!\\\\)((\\\\\\\\)*)\\\\c/",
			"/(?<!\\\\)((\\\\\\\\)*)\\\\p/",
			"/(?<!\\\\)((\\\\\\\\)*)\\\\l/",
			"/(?<!\\\\)((\\\\\\\\)*)\\\\g/",
			"/(?<!\\\\)((\\\\\\\\)*)\\\\n/"
		), array(
			"$1{{",
			"$1}}",
			"$1|",
			"$1<",
			"$1>",
			"$1\n"
		), $text);
		$text = str_replace("\\\\", "\\", $text);

		return $text;
	}
}
?>