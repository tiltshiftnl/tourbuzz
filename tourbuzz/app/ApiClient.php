<?php

require_once "ApiResponse.php";

use GuzzleHttp\Client;
use GuzzleHttp\Message\Request;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Exception\BadResponseException;

class ApiClient {
    private $_guzzle;
    private $_apiRoot;
    private $_token;

    public function __construct($url) {
        $this->_guzzle = new \GuzzleHttp\Client();
        $this->_apiRoot = $url;
    }

    public function setToken($token) {
        $this->_token = $token;
    }

    public function getApiRoot() {
        return $this->_apiRoot;
    }

    public function get($uri) {
        $requestUri = "{$this->_apiRoot}{$uri}";

        try {
            $res = $this->_guzzle->request('GET', $requestUri, [
                'http_errors' => false
            ]);
        } catch (BadResponseException $exception) {
            return null;
        }

        return new ApiResponse($res);
    }

    public function post($uri, $fields) {
        $requestUri = "{$this->_apiRoot}{$uri}";

        if (!empty($this->_token)) {
            $requestUri .= "?token={$this->_token}";
        }

        try {
            $res = $this->_guzzle->request('POST', $requestUri, [
                'form_params' => $fields,
                'http_errors' => false
            ]);
        } catch (BadResponseException $exception) {
            return null;
        }

        return new ApiResponse($res);
    }

    public function put($uri, $fields) {
        $requestUri = "{$this->_apiRoot}{$uri}";

        if (!empty($this->_token)) {
            $requestUri .= "?token={$this->_token}";
        }

        try {
            $res = $this->_guzzle->request('PUT', $requestUri, [
                'form_params' => $fields,
                'http_errors' => false
            ]);
        } catch (BadResponseException $exception) {
            return null;
        }

        return new ApiResponse($res);
    }

    public function delete($uri, $fields = array()) {
        $requestUri = "{$this->_apiRoot}{$uri}";
        try {
            $res = $this->_guzzle->request('DELETE', $requestUri, [
                'form_params' => $fields,
                'http_errors' => false
            ]);
        } catch (BadResponseException $exception) {
            return null;
        }

        return new ApiResponse($res);
    }

    /* FIXME */
    public function deleteBerichten($uri, $ids = []) {

        // Url-ify the data for the POST (ids to delete).
        $fieldsString = '';
        foreach($ids as $id) { $fieldsString .= 'ids[]='.$id.'&'; }
        $fieldsString = rtrim($fieldsString, '&');

        if (empty($fieldsString)) {
            // Nothing to do.
            return true;
        }

        $requestUri = "{$this->_apiRoot}{$uri}?{$fieldsString}";
        if (!empty($this->_token)) {
            $requestUri .= "&token={$this->_token}";
        }

        try {
            $this->_guzzle->request('DELETE', $requestUri);
        } catch (BadResponseException $exception) {
            return null;
        }

        return true;
    }

}

