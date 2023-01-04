<?php

require dirname(__FILE__).'/../Settings.php';

/**
 * Settings Test
 *
 * @author Vitold Sedyshev
 */
class SettingsTest extends PHPUnit_Framework_TestCase {

    /**
     * Test on method "load"
     *
     * @group Settings
     */
    public function testLoad() {
        $s = new Settings(dirname(__FILE__).'/../settings/default.conf');
        $actual = $s->get('SomeValue', null);
        $this->assertEquals('1', $actual);
    }

    /**
     * Test on method "save"
     *
     * @group Settings
     */
    public function testSave() {
        $secretName = md5(microtime(true));
        $secretValue = 'topSecret';

        // Save iteration
        $s = new Settings(dirname(__FILE__).'/../settings/default.conf');
        $s->set($secretName, $secretValue);
        $s->save();

        // Load iteration
        $s = new Settings(dirname(__FILE__).'/../settings/default.conf');
        $actual = $s->get($secretName);

        $this->assertEquals($secretValue, $actual);
    }

}
