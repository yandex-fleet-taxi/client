<?php

namespace YandexFleetTaxi\Client\Tests\PageParser\Auth;

use PHPUnit\Framework\TestCase;
use YandexFleetTaxi\Client\PageParser\PassportYandexRu\Auth\Welcome as WelcomePageParser;
use YandexFleetTaxi\Client\PageParser\PassportYandexRu\Auth\Welcome\Data;

class WelcomeTest extends TestCase
{
    private function getTestPageContent()
    {
        return file_get_contents(__DIR__ . '/../../Textures/Pages/passport.yandex.ru/auth/welcome.html');
    }

    private function getExpectedData()
    {
        $data = new Data();

        return $data
            ->setCsrfToken('eb8fd51433180ff188fee8fe5a9c9596712e2e7c:1567171385750')
            ->setProcessUuid('84693b2f-cc3d-4e4e-8013-1bc7918515ba');
    }

    public function testGetData()
    {
        $html = $this->getTestPageContent();
        $parser = new WelcomePageParser();
        $parserData = $parser->getData($html);
        $expectedData = $this->getExpectedData();

        $this->assertEquals($expectedData, $parserData);
    }
}
