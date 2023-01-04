<?php

// Copyright (C) 2014 DLH
// See LICENSE.txt for the MIT license.

$wgExtensionCredits["parserhook"][] = array(
   "path" => __FILE__,
   "name" => "TimeConvert",
   "description" => "Provides a parser function and Scribunto Lua library to convert a time to a different time zone",
   "author" => "dlh",
   "url" => "http://github.com/dlh/MediaWiki-TimeConvert"
);

class TimeConvert
{
    public static function onParserFirstCallInit(&$parser)
    {
        $parser->setFunctionHook("timeconvert", "TimeConvert::timeconvert");
        return true;
    }

    public static function onScribuntoExternalLibraries($engine, &$extraLibraries)
    {
        $extraLibraries["mw.ext.timeconvert"] = "TimeConvertLua";
        return true;
    }

    public static function timeconvert($parser, $time="", $zoneName="", $format="")
    {
        try
        {
            $errors = array();
            if (empty($time))
            {
                $time = "now";
                $errors[] = wfMessage("timeconvert-notime", $time)->parse();
            }
            if (empty($zoneName))
            {
                $zoneName = "Etc/GMT";
                $errors[] = wfMessage("timeconvert-nozone", $zoneName)->parse();
            }
            if (empty($format))
            {
                $format = DateTime::ISO8601;
            }

            $dt = new DateTime($time);
            $dt->setTimezone(new DateTimeZone($zoneName));
            $formattedTime = $dt->format($format);

            if (!empty($errors))
            {
                global $wgLang;
                return "(" . $wgLang->commaList($errors) . ") " . $formattedTime;
            }
            return $formattedTime;
        }
        catch (Exception $e)
        {
            return $e->getMessage();
        }
    }

    private function __construct() {}
}

$wgHooks["ParserFirstCallInit"][] = "TimeConvert::onParserFirstCallInit";
$wgHooks["ScribuntoExternalLibraries"][] = "TimeConvert::onScribuntoExternalLibraries";
$wgAutoloadClasses["TimeConvertLua"] = __DIR__ . "/TimeConvertLua.php";
$wgExtensionMessagesFiles["TimeConvert"] = __DIR__ . "/TimeConvert.i18n.php";

?>
