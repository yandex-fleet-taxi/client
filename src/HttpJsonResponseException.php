<?php

namespace Likemusic\YandexFleetTaxiClient;

use Throwable;

class HttpJsonResponseException extends HttpResponseException
{
    private $jsonCode;
    private $jsonMessage;
    private $jsonDetails;

    public function __construct(
        string $message = "",
        int $code = 0,
        string $reasonPhrase = null,
        string $jsonCode = null,
        string $jsonMessage = null,
        array $jsonDetails = null,
        Throwable $previous = null
    )
    {
        parent::__construct($message, $code, $reasonPhrase, $previous);
        $this->jsonCode = $jsonCode;
        $this->jsonMessage = $jsonMessage;
        $this->jsonDetails = $jsonDetails;
    }

    public function getJsonCode()
    {
        return $this->jsonCode;
    }

    public function getJsonMessage()
    {
        return $this->jsonMessage;
    }

    public function getJsonDetails()
    {
        return $this->jsonDetails;
    }
}
