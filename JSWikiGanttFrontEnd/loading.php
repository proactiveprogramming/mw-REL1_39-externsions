<?php
class LoadJS {
    
    function setup($wgOut)
    {
        global $wgJSWGFEDir, $wgJSWGFEScriptDir, $wgJSWGFEtScriptVersion;
        $wgJSWGFEDir= rtrim(dirname(__FILE__), "/\ ");
        $wgJSWGFEScriptDir = "/mediawiki/extensions/JSWikiGanttFrontEnd";
        $wgJSWGFEtScriptVersion = 1;
        
        global $wgJSWGFEDir;
        
        $wgOut->addScriptFile(LoadJS::getCSSJSLink("lib/sftJSmsg.js"));
        $wgOut->addScriptFile(LoadJS::getCSSJSLink("lib/node_modules/moment/moment.js"));
        $wgOut->addScriptFile(LoadJS::getCSSJSLink("lib/node_modules/moment-business-days/index.js"));
        $wgOut->addScriptFile(LoadJS::getCSSJSLink("lib/jscolor.js"));
        // Note! This name should be the same as in JSWikiGantt extension
        $wgOut->addScriptFile(LoadJS::getCSSJSLink("date-functions.js"));
        $wgOut->addScriptFile(LoadJS::getCSSJSLink("JSWikiGanttFrontEnd.js"));

        return true;
    }

    function getCSSJSLink($strFileName)
    {
        global $wgJSWGFEtScriptVersion, $wgJSWGFEScriptDir;
        return "{$wgJSWGFEScriptDir}/{$strFileName}?{$wgJSWGFEtScriptVersion}";
    }
}
