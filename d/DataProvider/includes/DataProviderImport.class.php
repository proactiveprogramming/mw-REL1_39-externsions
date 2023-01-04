<?php

class DataProviderImport {

    /**
     * Make request for retrieve remote article
     *
     * @param string $serverName
     * @return string
     */
    protected function getContent($serverName) {
        $result = null;

        $curlHttpRequest = new Curl_Http_Request();
        $curlHttpRequest->setUrl( $serverName );

        $retrIn = microtime(true);
        $r = Curl_Http::factory()->request($curlHttpRequest);
        $retrOut = microtime(true);

        $responseCode = $r->getResponseCode();
        echo 'Response code: ' . $responseCode . PHP_EOL;

        // Checking result is valid
        if ($responseCode == 200) {
            $result = $r->getResponseBody();
        }

        return $result;
    }

    /**
     * Get Wikipedia content
     *
     * @param Title $title
     * @return null|string
     */
    public function getMWContent($title) {
        $result = null;

        // Make title as title
        if (is_string($title)) {
            $title = Title::newFromText($title);
        }

        // http://ru.wikipedia.org/w/api.php?action=query&prop=revisions&rvprop=content&format=json&titles=%D0%9B%D1%83%D0%BD%D0%B0
        $mwRequest = new MWRequest();
        $url = $mwRequest->getUrl(array('titles' => $title->getDbKey()));
        echo 'Make request: ' . $url . PHP_EOL;
        $content = $this->getContent($url);
        if (isset($content)) {
            $queryMWResponse = new QueryMWResponse($content);
            $result = $queryMWResponse->getContent();
        }

        return $result;
    }

}
