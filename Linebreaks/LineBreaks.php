<?php
class LineBreaks
{
    public static function onParserBeforeInternalParse(&$parser, &$text, &$strip_state)
    {
		global $wgLbUseBackslash;    
		$text = str_replace("  \n", "<br />\n", $text);
		if ($wgLbUseBackslash) {
			$text = str_replace("\\\n", "<br />\n", $text);
		}
		return true;
    }
}
