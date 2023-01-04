<?php

/*
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/*  Version 1.0.1
 */

function nbbc_iurl_tag($bbcode, $action, $name, $default, $params, $content) {
	if ($action == BBCODE_CHECK)
		return true;
	else	return '<a href="#'.htmlspecialchars($default).'">'.$content.'</a>';
}

function nbbc_anchor_tag($bbcode, $action, $name, $default, $params, $content) {
	if ($action == BBCODE_CHECK)
		return true;
	else	return '<a id="'.htmlspecialchars($default).'">'.$content.'</a>';
}

class BBCode_ex extends Nbbc\BBCode {
	/* Don't encode HTML, because the BBCode parser is called after the Wikitext parser, which outputs HTML,
	 * and we want that HTML to be parsed by the browser, not encoded for display by the browser.
	 */
	function HTMLEncode($string) {
		return $string;
	}
}

class NBBC_BBCode {
	static private $bbcode = null;

	static private function getBBCodeSingleton() {
		global $NBBC_BBCode_ParseWithinTagOnly;

		if (is_null(self::$bbcode)) {
			$bbcode = ($NBBC_BBCode_ParseWithinTagOnly ? new \Nbbc\BBCode : new BBCode_ex);
			self::$bbcode = $bbcode;

			/* Add a couple of custom BBCode tags: [iurl] and [anchor].
			 * Not strictly necessary but I find them useful.
			 */
			$bbcode->AddRule('iurl', Array(
				'mode' => Nbbc\BBCode::BBCODE_MODE_CALLBACK,
				'method' => 'nbbc_iurl_tag',
				'class' => 'inline',
				'allow_in' => Array('listitem', 'block', 'columns'),
			));
			$bbcode->AddRule('anchor', Array(
				'mode' => Nbbc\BBCode::BBCODE_MODE_CALLBACK,
				'method' => 'nbbc_anchor_tag',
				'class' => 'inline',
				'allow_in' => Array('listitem', 'block', 'columns'),
			));
		}

		return self::$bbcode;
	}

	static public function onParserFirstCallInit(Parser &$parser) {
		global $NBBC_BBCode_ParseWithinTagOnly;

		if ($NBBC_BBCode_ParseWithinTagOnly) {
			$parser->setHook('bbcode', function($text, $params, $parser, $frame) {
				$bbcode = self::getBBCodeSingleton();
				return $bbcode->Parse($text);
			});
		}
	}

	static public function onParserAfterParse(Parser &$parser, &$text, &$strip_state) {
		global $NBBC_BBCode_ParseWithinTagOnly;

		if (!$NBBC_BBCode_ParseWithinTagOnly) {
			$bbcode = self::getBBCodeSingleton();
			// Don't convert the newlines in the existing HTML output to <br>s -
			// that would mess up the output with extraneous blank lines.
			$bbcode->setIgnoreNewlines(true);
			$text = $bbcode->Parse($text);
		}

		return true;
	}
}
