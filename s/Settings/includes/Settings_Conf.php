<?php

class Settings_Conf {

    protected $_attributes = array();

    /**
     * Quote char
     *
     * @var string
     */
    protected $_quote = '"';

    /**
     * Construct
     *
     * @param string $name
     */
    public function __construct($name = null) {
        if ($name !== null) {
            $this->load($name);
        }
    }

    /**
     * Load settings in memory
     *
     * @param string $path
     */
    public function load($path) {
        $content = file_get_contents($path);
        $items = explode("\n", $content);
        foreach($items as $item) {
            $item = trim($item);
            if (isset($item) and is_string($item)) {
                // Skip comment
                $itemLen = strlen($item);
                if ($itemLen == 0) {
                    continue;
                }
                if ($item[0] === '#') {
                    continue;
                }

                // Starting parse
                $keyValue = explode('=', $item, 2);
                if ($keyValue) {
                    list($name, $value) = $keyValue;
                    $name = trim($name);
                    $value = trim($value);
                    $value = $this->decode($value);
                    $this->_attributes[$name] = $value;
                }
            }
        }
    }

    /**
     * Unquote
     *
     * @param string $value
     * @return null|string
     */
    protected function unquote($value) {
        $result = null;
        $len = strlen($value);
        $quoteLen = strlen($this->_quote);
        if ((substr($value, 0, $quoteLen) === $this->_quote) and (substr($value, $len - $quoteLen, $quoteLen) === $this->_quote)) {
            $result = substr($value, $quoteLen, $len - 2*$quoteLen);
        }
        return $result;
    }

    /**
     * Quote this string
     *
     * @param string $value
     * @return string
     */
    protected function quote($value) {
        $result = $this->_quote . $value . $this->_quote;
        return $result;
    }

    protected function decode($value) {
        $result = strtr($value, array(
            '\r' => "\r",
            '\n' => "\n",
            '\\\\' => '\\'
        ));
        $result = $this->unquote($result);
        return $result;
    }

    /**
     * Encode some string
     *
     * @param string $value
     * @return string
     */
    protected function encode($value) {
        $result = strtr($value, array(
            '\\' => '\\\\',
            "\n" => '\n',
            "\r" => '\r'));
        $result = $this->quote($result);
        return $result;
    }

    /**
     * Save settings from memory
     *
     * @param string $path
     */
    public function save($path) {
        $items = array();
        foreach($this->_attributes as $name => $value) {
            $items[] = $name . '=' . $this->encode($value);
        }
        file_put_contents($path, join("\n", $items));
    }

    /**
     * @param string $name
     * @param mixed $defaultValue
     * @return null|string
     */
    public function get($name, $defaultValue = null) {
        $result = $defaultValue;
        if (array_key_exists($name, $this->_attributes)) {
            $result = $this->_attributes[$name];
        }
        return $result;
    }

    /**
     * Set value
     *
     * @param string $name
     * @param string $value
     */
    public function set($name, $value) {
        $this->_attributes[$name] = $value;
    }

}
