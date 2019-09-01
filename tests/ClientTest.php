<?php
declare(strict_types=1);

namespace Likemusic\YandexFleetTaxiClient\Tests;

use Http\Client\Exception as HttpClientException;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\Psr17FactoryDiscovery;
use Likemusic\YandexFleetTaxiClient\Client;
use Likemusic\YandexFleetTaxiClient\Exception as ClientException;
use Likemusic\YandexFleetTaxiClient\PageParser\FleetTaxiYandexRu\Index as DashboardPageParser;
use Likemusic\YandexFleetTaxiClient\PageParser\PassportYandexRu\Auth\Welcome as WelcomePageParser;
use Likemusic\YandexFleetTaxiClient\Tests\PageParser\FleetTaxiYandexRu\IndexTest;
use PHPUnit\Framework\TestCase;
use Likemusic\YandexFleetTaxiClient\Contracts\LocaleInterface;

final class ClientTest extends TestCase
{
    /**
     * @return Client
     * @throws ClientException
     * @throws HttpClientException
     * @doesNotPerformAssertions
     */
    public function testLogin()
    {
        $httpClient = HttpClientDiscovery::find();
        $requestFactory = Psr17FactoryDiscovery::findRequestFactory();
        $welcomePageParser = new WelcomePageParser();
        $streamFactory = Psr17FactoryDiscovery::findStreamFactory();
        $dashboardPageParser = new DashboardPageParser();

        $client = new Client($httpClient, $requestFactory, $streamFactory, $welcomePageParser, $dashboardPageParser);

        $login = 'socol-test';
        $password = 's12346';
        $rememberMe = true;
        $client->login($login, $password, $rememberMe);

        return $client;
    }

    /**
     * @param Client $client
     * @return Client
     * @throws HttpClientException
     * @throws ClientException
     * @depends testLogin
     */
    public function testGetDashboardPageData(Client $client)
    {
        $dashboardPageData = $client->getDashboardPageData();
        $this->assertEquals(IndexTest::EXPECTED_DATA_LANG_DEFAULT, $dashboardPageData);

        return $client;
    }

    /**
     * @param Client $client
     * @throws ClientException
     * @throws HttpClientException
     * @depends testGetDashboardPageData
     */
    public function testChangeLocale(Client $client)
    {
        $dashboardPageData = $client->changeLanguage(LocaleInterface::RUSSIAN);
        $this->assertEquals(IndexTest::EXPECTED_DATA_LANG_RUSSIAN, $dashboardPageData);
    }
}
