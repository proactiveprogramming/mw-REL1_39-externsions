<?php

class MWResponse {

    /**
     * @var array
     */
    protected $_data = array();

    /**
     * @param string $content
     */
    public function __construct($content) {
        $this->_data = json_decode($content, true);
    }


}
