<?php

class ApiResponse {
    /**
     * @var int $statusCode
     */
    protected $statusCode;

    /**
     * @var string $body
     */
    protected $body;

    public function __construct($res) {
        $this->statusCode = $res->getStatusCode();
        $this->body       = json_decode($res->getBody(), true);
    }

    /**
     * @return int
     */
    public function getStatusCode() {
        return $this->statusCode;
    }

    /**
     * @return string
     */
    public function getBody() {
        return $this->body;
    }


}