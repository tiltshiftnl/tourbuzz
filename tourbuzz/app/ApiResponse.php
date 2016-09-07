<?php

class ApiResponse {

    /**
     * @var int $statusCode
     */
    public $statusCode;

    /**
     * @var string $body
     */
    public $body;

    public function __construct($res) {
        $this->statusCode = $res->getStatusCode();
        $this->body       = json_decode($res->getBody(), true);
    }

}