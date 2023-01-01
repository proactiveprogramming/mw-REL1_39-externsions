<?php

class Curl_Http_Request {

    const HTTP_GET  = 1;
    const HTTP_POST = 2;

    /**
     * @var
     */
    public $url;

    /**
     * @var
     */
    public $method;

    /**
     * @var array
     */
    protected $_post = array();

    /**
     * @var string
     */
    public $userAgent = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.4 (KHTML, like Gecko) Chrome/22.0.1229.94 Safari/537.4';

    /**
     * Timeout (ms)
     *
     * @var int
     */
    protected $_timeout = 5000;

    /**
     * @param string $name
     * @param string $value
     */
    public function setParameterPost($name, $value) {
        $this->_post[$name] = $value;
    }

    /**
     * @return array
     */
    public function getPost() {
        return $this->_post;
    }

    /**
     * @return string
     */
    public function getQuery() {
        $result = null;

        $pairs = array();
        foreach($this->_post as $name => $value) {
            $pairs[] = $name . '=' . $value;
        }
        $result = join('&', $pairs);
        return $result;
    }

    /**
     * ...
     *
     * @param string $url
     */
    public function setUrl($url) {
        $this->url = $url;
    }

    /**
     * ...
     *
     * @param array $queryData
     */
    public function setQueryData($queryData = array()) {
        foreach($queryData as $name => $value) {
            $this->setParameterPost($name, $value);
        }
    }

    public function setTimeout($timeout) {
        $this->_timeout = $timeout;
    }

    public function getTimeout() {
        return $this->_timeout;
    }

}
