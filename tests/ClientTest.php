<?php
declare(strict_types=1);

namespace Likemusic\YandexFleetTaxiClient\Tests;

use Likemusic\YandexFleetTaxiClient\Client;
use PHPUnit\Framework\TestCase;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\MessageFactoryDiscovery;
use Http\Discovery\Psr17FactoryDiscovery;
use \Likemusic\YandexFleetTaxiClient\PageParser\PassportYandexRu\Auth\Welcome as WelcomePageParser;
use Http\Message\StreamFactory;
use Likemusic\YandexFleetTaxiClient\Tests\PageParser\FleetTaxiYandexRu\IndexTest;
use Likemusic\YandexFleetTaxiClient\PageParser\FleetTaxiYandexRu\Index as DashboardPageParser;

final class ClientTest extends TestCase
{
    /**
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
     * @depends testLogin
     */
    public function testGetDashboardPageData(Client $client)
    {
        $dashboardPageData = $client->getDashboardPageData();
        $this->assertEquals(IndexTest::EXPECTED_DATA, $dashboardPageData);
    }
}
