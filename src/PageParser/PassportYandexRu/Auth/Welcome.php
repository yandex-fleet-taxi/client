<?php

namespace YandexFleetTaxi\Client\PageParser\PassportYandexRu\Auth;

use DOMNode;
use DOMXPath;
use YandexFleetTaxi\Client\PageParser\Base as BaseHtmlParser;
use YandexFleetTaxi\Client\PageParser\PassportYandexRu\Auth\Welcome\Data as WelcomePageParserData;

class Welcome extends BaseHtmlParser
{
    const XPATH_CSRF_TOKEN = '//input[@name="csrf_token"]/@value';
    const XPATH_PROCESS_UUID = '//div[@class="passp-auth-header"]/a/@href';

    public function getData($html): WelcomePageParserData
    {
        return parent::getData($html);
    }

    protected function getDataByDomXPath(DOMXPath $DOMXPath)
    {
        $csrfToken = $this->getCsrfToken($DOMXPath);
        $processUuid = $this->getProcessUuid($DOMXPath);

        $ret = new WelcomePageParserData();

        return $ret
            ->setCsrfToken($csrfToken)
            ->setProcessUuid($processUuid);
    }

    private function getCsrfToken(DOMXPath $DOMXPath): string
    {
        return $this->getFirstValueByXPath($DOMXPath, self::XPATH_CSRF_TOKEN);
    }

    private function getProcessUuid(DOMXPath $DOMXPath): string
    {
        $href = $this->getFirstValueByXPath($DOMXPath,self::XPATH_PROCESS_UUID);

        return $this->getProcessUuidByHref($href);
    }

    private function getProcessUuidByHref($href)
    {
        $query = parse_url($href, PHP_URL_QUERY);
        parse_str($query, $result);

        return $result['process_uuid'];
    }
}
