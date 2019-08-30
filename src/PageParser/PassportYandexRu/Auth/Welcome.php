<?php

namespace Likemusic\YandexFleetTaxiClient\PageParser\PassportYandexRu\Auth;

use Likemusic\YandexFleetTaxiClient\PageParser\Base as BaseHtmlParser;

class Welcome extends BaseHtmlParser
{
    const XPATH_CSRF_TOKEN = '//input[@name="csrf_token"]';
    const XPATH_PROCESS_UUID = '//div[@class="passp-auth-header"]/a/@href';

    public function getCsrfToken(): string
    {
        return $this->getByXpath(self::XPATH_CSRF_TOKEN);
    }

    public function getProcessUuid(): string
    {
        $href = $this->getByXpath(self::XPATH_PROCESS_UUID);

        return $this->getProcessUuidByHref($href);
    }

    private function getProcessUuidByHref($href)
    {
        $query = parse_url($href, PHP_URL_QUERY);
        parse_str($query, $result);

        return $result['process_uuid'];
    }
}
