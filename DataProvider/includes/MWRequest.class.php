<?php

/**
 * MediaWiki Request
 *
 * @package
 */
class MWRequest {

    /**
     * Target WikiMedia Server Name
     *
     * @var string
     */
    protected $_server;

    /**
     * @var string
     */
    protected $_action;

    /**
     * @var string
     */
    protected $_format;

    /**
     * @param string $server
     */
    public function setServer($server) {
        $this->_server = $server;
    }

    /**
     * @return string
     */
    public function getServer() {
        return $this->_server;
    }

    /**
     *
     */
    public function setAction($action) {
        $this->_action = $action;
    }

    /**
     * @param string $format allowed format is JSON, XML, ...
     */
    public function setFormat($format) {
        $this->_format = $format;
    }

    /**
     * Get request URL
     *
     * @param array $data additional or override parameters
     * @return null|string
     */
    public function getUrl($data = array()) {
        $defaultData = array(
            'action' => 'query',
            'prop'   => 'revisions',
            'rvprop' => 'content',
            'format' => 'json',
            'titles' => '',
        );
        $data = array_merge($defaultData, $data);
        $queryString = http_build_query($data);
        $result = 'http://ru.wikipedia.org/w/api.php?' . $queryString;
        return $result;
    }

}