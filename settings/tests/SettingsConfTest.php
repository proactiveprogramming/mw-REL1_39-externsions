<?php

require "../includes/Settings_Conf.php";

/**
 * Setting Conf Driver
 *
 * @author Vitold Sedyshev
 */
class SettingConfTest extends PHPUnit_Framework_TestCase {

    public function testQuote() {
        $s = new Settings_Conf_Mock();
        $actual = $s->quote('SomeString');
        $this->assertEquals('"SomeString"', $actual);
    }

    public function testUnQuote() {
        $s = new Settings_Conf_Mock();
        $actual = $s->unquote('"SomeString"');
        $this->assertEquals('SomeString', $actual);
    }

}

class Settings_Conf_Mock extends Settings_Conf {
    public function quote($value) {
        return parent::quote($value);
    }
    public function unquote($value) {
        return parent::unquote($value);
    }
}