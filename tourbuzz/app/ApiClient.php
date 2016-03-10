<?php

class ApiClient {
    private $_guzzle;
    private $_apiRoot;

    public function __construct($url) {
        $this->_guzzle = new \GuzzleHttp\Client();
        $this->_apiRoot = $url;
    }

    public function getApiRoot() {
        return $this->_apiRoot;
    }

    public function get($uri) {
        $requestUri = "{$this->_apiRoot}{$uri}";
        $res = $this->_guzzle->request('GET', $requestUri);
        return json_decode($res->getBody(), true);
    }

    public function post($uri, $fields) {
        $requestUri = "{$this->_apiRoot}{$uri}";
        $res = $this->_guzzle->request('POST', $requestUri, [
            'form_params' => $fields
        ]);
        return json_decode($res->getBody(), true);
    }

    public function delete($uri, $ids) {
        $ids = $ids ? $ids : [];

        //url-ify the data for the POST
        $fieldsString = '';
        foreach($ids as $id) { $fieldsString .= 'ids[]='.$id.'&'; }
        $fieldsString = rtrim($fieldsString, '&');

        $requestUri = "{$this->_apiRoot}{$uri}?{$fieldsString}";

        $this->_guzzle->request('DELETE', $requestUri);
    }
}
