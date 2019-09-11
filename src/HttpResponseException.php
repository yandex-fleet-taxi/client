<?php

namespace Likemusic\YandexFleetTaxiClient;

use Throwable;

class HttpResponseException extends Exception
{
    /**
     * @var ?string
     */
    private $reasonPhrase;

    public function __construct(string $message = "", int $code = 0, string $reasonPhrase = null, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->reasonPhrase = $reasonPhrase;
    }

    /**
     * @return string
     */
    public function getReasonPhrase(): ?string
    {
        return $this->reasonPhrase;
    }
}
