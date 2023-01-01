<?php

class Curl_Http {

    /**
     * Тут можно описывать параметры прокси
     *
     * @var array
     */
    public $proxy = array();

    /**
     * @return \Curl_Http
     */
    public static function factory() {
        return new self();
    }

    /**
     * Осуществляем запрос к серверу
     *
     * @param Curl_Http_Request $curlHttpRequest
     * @return Curl_Http_Response
     */
    public function request( Curl_Http_Request $curlHttpRequest ) {

        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $curlHttpRequest->url );
        curl_setopt( $ch, CURLOPT_USERAGENT, $curlHttpRequest->userAgent );
        curl_setopt( $ch, CURLOPT_TIMEOUT_MS, $curlHttpRequest->getTimeout() );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
//        curl_setopt( $ch, CURLOPT_VERBOSE, 1);

        if ($curlHttpRequest->method == Curl_Http_Request::HTTP_POST) {
            $data = $curlHttpRequest->getQuery();

            curl_setopt( $ch, CURLOPT_POST, 1);
            curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );

        } else {
            curl_setopt( $ch, CURLOPT_HTTPGET, 1);
        }

        $result = new Curl_Http_Response();

        $result->output = curl_exec( $ch );

        $info = curl_getinfo( $ch );

        $result->code = $info['http_code'];

        curl_close($ch);

        return $result;
    }

}