<?php

/**
 * Ответ HTTP от сервера
 *
 * @author Vitold Sedyshev
 * @package Component.Curl
 */
class Curl_Http_Response {

    public $code;
    public $output;

    /**
     * Возвращает истину в случае успешного заверщения
     *
     * @param array $codes
     * @return bool
     */
    public function isSuccess($codes = array(200)) {
        $result = in_array($this->code, $codes);
        return $result;
    }

    /**
     * @return int
     */
    public function getResponseCode() {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getResponseBody() {
        return $this->output;
    }

}
