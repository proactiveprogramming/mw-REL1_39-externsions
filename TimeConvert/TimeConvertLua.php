<?php

class TimeConvertLua extends Scribunto_LuaLibraryBase
{
    public function register()
    {
        $lib = array("timeconvert" => array($this, "timeconvert"));
        $this->getEngine()->registerInterface( __DIR__ . "/TimeConvert.lua", $lib);
    }

    public function timeconvert($time="", $zoneName="", $format="")
    {
        return array(TimeConvert::timeconvert(null, $time, $zoneName, $format));
    }
}

?>
