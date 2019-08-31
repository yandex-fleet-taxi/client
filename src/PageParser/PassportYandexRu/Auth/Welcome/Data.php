<?php

namespace Likemusic\YandexFleetTaxiClient\PageParser\PassportYandexRu\Auth\Welcome;

class Data
{
    private $csrfToken;
    private $processUuid;

    public function setCsrfToken(string $csrfToken): self
    {
        $this->csrfToken = $csrfToken;

        return $this;
    }

    public function getCsrfToken()
    {
        return $this->csrfToken;
    }

    public function setProcessUuid(string $processUuid): self
    {
        $this->processUuid = $processUuid;

        return $this;
    }

    public function getProcessUuid(): string
    {
        return $this->processUuid;
    }
}
