<?php

namespace Likemusic\YandexFleetTaxiClient\Tests\PageParser\FleetTaxiYandexRu;

use Likemusic\YandexFleetTaxiClient\PageParser\FleetTaxiYandexRu\Index as IndexPageParser;
use PHPUnit\Framework\TestCase;

class IndexTest extends TestCase
{
    const EXPECTED_DATA = [
        0 =>
            [
                'id' => '8d40b7c41af544afa0499b9d0bdf2430',
                'clid' => '400000110566',
                'city' => 'Stavropol',
                'city_original' => 'Ставрополь',
                'country_id' => 'rus',
                'country_name' => 'Россия',
                'currency_id' => 'RUB',
                'locale' => 'ru',
                'sub_agggregation' => false,
                'timezone' => 3,
                'self_employed' => false,
                'franchising' => false,
                'providers' =>
                    [
                        0 => 'park',
                        1 => 'yandex',
                    ],
                'short_name' => 'Сокол РФ 26',
                'name' => 'Сокол РФ 26, Stavropol',
                'brand' => 1,
            ],
    ];

    public function testGetData()
    {
        $html = $this->getTestPageContent();
        $parser = new IndexPageParser();
        $parserData = $parser->getData($html);

        $this->assertEquals(self::EXPECTED_DATA, $parserData);
    }

    private function getTestPageContent()
    {
        return file_get_contents(__DIR__ . '/../../Textures/Pages/fleet.taxi.yandex.ru/index.html');
    }
}
