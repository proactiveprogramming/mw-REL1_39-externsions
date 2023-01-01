<?php

require dirname(__FILE__) . '/includes/Settings_XML.php';
require dirname(__FILE__) . '/includes/Settings_Conf.php';

/**
 * Settings
 *
 * @author Vitold Sedyshev
 */
class Settings
{


    public function __construct($name)
    {
        $this->_name = $name;
    }

    /**
     * @return Settings_Conf
     */
    public function getConfigStore()
    {
        static $instance;
        if ($instance === null) {
            //$instance = new Settings_XML($this->_name);
            $instance = new Settings_Conf($this->_name);
        }
        return $instance;
    }

    /**
     * @param string $name
     * @param mixed $defaultValue
     * @return null|string
     */
    public function get($name, $defaultValue = null)
    {
        return $this->getConfigStore()->get($name, $defaultValue);
    }

    /**
     */
    public function set($name, $value)
    {
        $this->getConfigStore()->set($name, $value);
    }

    /**
     * Manual save changes
     *
     * @return void
     */
    public function save()
    {
        $this->getConfigStore()->save($this->_name);
    }

}

$wgSettingsName = empty($wgSettingsName) ? "$IP/settings/settings/default.conf" : $wgSettingsName;
$wgSettings = new Settings($wgSettingsName);
