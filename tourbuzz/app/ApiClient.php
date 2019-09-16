<?php

require_once "ApiResponse.php";

use GuzzleHttp\Client;
use GuzzleHttp\Message\Request;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Exception\BadResponseException;
use Psr\Log\LoggerInterface;

class ApiClient {
    /**
     * @var \GuzzleHttp\Client
     */
    private $_guzzle;
    private $_apiRoot;
    private $_token;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct($url, LoggerInterface $logger) {
        $this->_guzzle = new \GuzzleHttp\Client();
        $this->_apiRoot = $url;
        $this->logger = $logger;
    }

    public function setToken($token) {
        $this->_token = $token;
    }

    public function getApiRoot() {
        return $this->_apiRoot;
    }

    public function get($uri) {
        $requestUri = "{$this->_apiRoot}{$uri}";

        if (!empty($this->_token)) {
            $requestUri .= "?token={$this->_token}";
        }

        try {
            /* @var $res \Psr\Http\Message\ResponseInterface */
            $res = $this->_guzzle->request('GET', $requestUri, [
                'http_errors' => false
            ]);
            if ($res->getStatusCode() >= 500) {
                $this->logger->error('Error response from tourbuzz API', ['method' => 'GET', 'uri' => $uri, 'status_code' => $res->getStatusCode()]);
            }
        } catch (\Exception $e) {
            $this->logger->error('Exception while loading info from tourbuzz API', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
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
            if ($res->getStatusCode() >= 500) {
                $this->logger->error('Error response from tourbuzz API', ['method' => 'POST', 'uri' => $uri, 'status_code' => $res->getStatusCode()]);
            }
        } catch (\Exception $e) {
            $this->logger->error('Exception while loading info from tourbuzz API', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
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
            if ($res->getStatusCode() >= 500) {
                $this->logger->error('Error response from tourbuzz API', ['method' => 'PUT', 'uri' => $uri, 'status_code' => $res->getStatusCode()]);
            }
        } catch (\Exception $e) {
            $this->logger->error('Exception while loading info from tourbuzz API', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
        }

        return new ApiResponse($res);
    }

    public function delete($uri, $fields = array()) {
        $requestUri = "{$this->_apiRoot}{$uri}";

        if (!empty($this->_token)) {
            $requestUri .= "?token={$this->_token}";
        }

        try {
            $res = $this->_guzzle->request('DELETE', $requestUri, [
                'form_params' => $fields,
                'http_errors' => false
            ]);
            if ($res->getStatusCode() >= 500) {
                $this->logger->error('Error response from tourbuzz API', ['method' => 'DELETE', 'uri' => $uri, 'status_code' => $res->getStatusCode()]);
            }
        } catch (\Exception $e) {
            $this->logger->error('Exception while loading info from tourbuzz API', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
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
            $res = $this->_guzzle->request('DELETE', $requestUri);
            if ($res->getStatusCode() >= 500) {
                $this->logger->error('Error response from tourbuzz API', ['method' => 'DELETE', 'uri' => $uri, 'status_code' => $res->getStatusCode()]);
            }
        } catch (Exception $e) {
            $this->logger->error('Exception while loading info from tourbuzz API', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
        }

        return true;
    }

}

