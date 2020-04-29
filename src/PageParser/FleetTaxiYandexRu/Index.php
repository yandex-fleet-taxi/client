<?php

namespace YandexFleetTaxi\Client\PageParser\FleetTaxiYandexRu;

use DOMXPath;
use YandexFleetTaxi\Client\PageParser\Base as BaseHtmlParser;
use YandexFleetTaxi\Client\PageParser\Base as BasePageParser;

class Index extends BaseHtmlParser
{
    protected function getDataByDomXPath(DOMXPath $DOMXPath)
    {
        $dataScript = $this->getDataScript($DOMXPath);

        return $this->getDataFromDataScript($dataScript);
    }

    private function getDataFromDataScript(string $dataScript) : array
    {
        $parksJson = $this->getParksJson($dataScript);

        return json_decode($parksJson,true);
    }

    private function getParksJson($script)
    {
        $pattern = '/window.parks = (?<json>.*);$/m';
        $matches = [];
        preg_match($pattern, $script, $matches);

        return $matches['json'];
    }

    private function getDataScript(DOMXPath $DOMXPath) : string
    {
        return $this->getFirstValueByXPath($DOMXPath, '/html/head/script[1]');
    }
}
