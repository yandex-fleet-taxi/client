<?php

namespace Likemusic\YandexFleetTaxiClient\PageParser;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMNodeList;
use DOMXPath;

abstract class Base
{
    public function getData($html)
    {
        $domXpath = $this->getDomXpathByHtml($html);

        return $this->getDataByDomXPath($domXpath);
    }

    abstract protected function getDataByDomXPath(DOMXPath $DOMXPath);

    private function getDomXpathByHtml(string $html)
    {
        libxml_use_internal_errors(true);
        $domDocument = new DOMDocument();
        $domDocument->loadHTML($html);
        libxml_use_internal_errors(false);

        return new DOMXPath($domDocument);
    }

    protected function getByXpath(DOMXPath $DOMXPath, string $xpath) : DOMNodeList
    {
        return $DOMXPath->evaluate($xpath);
    }

    protected function getFirstByXPath(DOMXPath $DOMXPath, string $xPathQuery) : DOMNode
    {
        $domNodeList = $this->getByXPath($DOMXPath, $xPathQuery);

        return $domNodeList->item(0);
    }

    protected function getFirstValueByXPath(DOMXPath $DOMXPath, $xPathQuery) : string
    {
        return $this->getFirstByXPath($DOMXPath, $xPathQuery)->nodeValue;
    }

}
