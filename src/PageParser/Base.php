<?php

namespace Likemusic\YandexFleetTaxiClient\PageParser;

use DOMDocument;
use DOMXPath;

class Base
{
    /**
     * @var DOMXPath
     */
    private $domXPath;

    public function __construct($html)
    {
        libxml_use_internal_errors(true);
        $domDocument = new DOMDocument();
        $domDocument->loadHTML($html);
        libxml_use_internal_errors(false);

        $this->domXPath = new DOMXPath($domDocument);
    }

    protected function getByXpath(string $xpath)
    {
        return $this->domXPath->evaluate($xpath);
    }
}
