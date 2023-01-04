<?php
class MathJax_Parser {
    static $Markers;
    static $mark_n = 0;

    private static $moduleLoaded = false;

    public static function onRegistration() {
        Hooks::register( 'ParserFirstCallInit', __CLASS__ . '::setup' );
    }

    public static function setup( Parser $parser )
    {
        $parser->setHook( 'nomathjax' , 'MathJax_Parser::NoMathJax' );

        global $wgOut, $wgMjUseCDN, $wgMjSize, $wgMjUseChem, $wgMjInlineMath;

        $mjModule = $wgMjUseCDN ? 'ext.MjCDN' : 'ext.MjLocal';
        $wgOut->addModules( $mjModule );
        // MobileFrontend requires explicit cloned modules targeting mobile
        $wgOut->addModules( $mjModule . ".mobile" );

        $wgOut->addJsConfigVars( 'wgMjSize', $wgMjSize );
    }

    static function RemoveMathTags(&$parser, &$text)
    {
        $text = preg_replace('|:\s*<math>(.*?)</math>|s', '\\[$1\\]', $text);
        $text = preg_replace('|<math>(.*?)</math>|s',     '\\($1\\)', $text);

        return true;
    }

    static function ReplaceByMarkers(Parser &$parser, &$text )
    {
        $text = preg_replace_callback('/(\$\$)(.*?)(\$\$)/s',                         'MathJax_Parser::Marker',$text);
        // Disable $...$ replace because breaks too many things
        // $text = preg_replace_callback('|(?<![\{\/\:\\\\])(\$)(.*?)(?<![\\\\])(\$)|s', 'MathJax_Parser::Marker', $text);
        $text = preg_replace_callback('/(\\\\\[)(.*?)(\\\\\])/s',                     'MathJax_Parser::Marker', $text);
        $text = preg_replace_callback('/(\\\\\()(.*?)(\\\\\))/s',                     'MathJax_Parser::Marker', $text);
        $text = preg_replace_callback('/(\\\begin{(?:.*?)})(.*?)(\\\end{(?:.*?)})/s', 'MathJax_Parser::Marker', $text);

        return true;
    }

    static function NoMathJax( $text, array $args, Parser $parser, PPFrame $frame )
    {
        $output = $parser->recursiveTagParse($text, $frame);

        return '<span class="tex2jax_ignore">' . $output . '</span>';
    }

    static function RemoveMarkers( Parser &$parser, &$text )
    {
    /** <!-- array_values seems to return a string with "..." instead of '...' so that double backslashes (\\)
     *  becomes a single backslash (\) so that I do need the MarkerVal function since modifying a code
     *  in math mode to match these backslashes may cause unexpected behaviors. -->
     **/
    #$text = preg_replace(array_keys(self::$Markers), array_values(self::$Markers), $text);
        $text = preg_replace_callback('/' . Parser::MARKER_PREFIX . 'MathJax(?:.*?)' . Parser::MARKER_SUFFIX . '/s', 'MathJax_Parser::MarkerVal', $text);

        return true;
    }

    static function MarkerVal($matches)
    {
        return self::$Markers[$matches[0]];
    }


    static function Marker($matches)
    {
        $marker = Parser::MARKER_PREFIX . 'MathJax' . ++self::$mark_n . Parser::MARKER_SUFFIX;

        $matches[2] = str_replace('<', '&#60;', $matches[2]);
        $matches[2] = str_replace('>', '&#62;', $matches[2]);
        self::$Markers[$marker] =  $matches[1] . $matches[2] . $matches[3];

        return $marker;
    }
}

