<?php

class Settings_XML {


    public function __construct($name) {
        $this->init();
    }

    public function init() {
        $this->_dom = new DomDocument();
    }

    public function get($name) {

    }

    public function set($name, $value) {
    }

    public function save($name) {
        $content = $this->_dom->saveXML();
        // ...
    }

    public function load($name) {
        $content = file_get_contents($name);
        $this->_dom->loadXML($content);
    }

}
