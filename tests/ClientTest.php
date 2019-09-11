<?php
declare(strict_types=1);

namespace Likemusic\YandexFleetTaxiClient\Tests;

use Http\Client\Curl\Client as CurlClient;
use Http\Client\Exception as HttpClientException;
use Http\Discovery\Psr17FactoryDiscovery;
use Likemusic\YandexFleetTaxiClient\Client;
use Likemusic\YandexFleetTaxiClient\Contracts\LanguageInterface;
use Likemusic\YandexFleetTaxiClient\Exception as ClientException;
use Likemusic\YandexFleetTaxiClient\PageParser\FleetTaxiYandexRu\Index as DashboardPageParser;
use Likemusic\YandexFleetTaxiClient\PageParser\PassportYandexRu\Auth\Welcome as WelcomePageParser;
use Likemusic\YandexFleetTaxiClient\Tests\FixtureInterface;
use Likemusic\YandexFleetTaxiClient\Tests\PageParser\FleetTaxiYandexRu\IndexTest;
use PHPUnit\Framework\TestCase;

final class ClientTest extends TestCase
{
    const BRAND_NAME = 'Alfa Romeo';
    const DRIVER_ID = 'cfa844ddca5e0290dc282086ade844d8';
    const CAR_ID = 'f9430230414bf8257e3355e8c2985c5f';

    /**
     * @return Client
     * @throws ClientException
     * @throws HttpClientException
     * @doesNotPerformAssertions
     */
    public function testLogin()
    {
        $options = [
            CURLOPT_PROXY => 'host.docker.internal:8888',
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
        ];

        $httpClient = new CurlClient(null, null, $options);
        $requestFactory = Psr17FactoryDiscovery::findRequestFactory();
        $welcomePageParser = new WelcomePageParser();
        $streamFactory = Psr17FactoryDiscovery::findStreamFactory();
        $dashboardPageParser = new DashboardPageParser();

        $client = new Client(
            $httpClient,
            $requestFactory,
            $streamFactory,
            $welcomePageParser,
            $dashboardPageParser
        );

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
     * @return Client
     * @throws ClientException
     * @throws HttpClientException
     * @depends testGetDashboardPageData
     */
    public function testChangeLocale(Client $client): Client
    {
        $dashboardPageData = $client->changeLanguage(LanguageInterface::RUSSIAN);
        $this->assertEquals(IndexTest::EXPECTED_DATA_LANG_RUSSIAN, $dashboardPageData);

        return $client;
    }

    /**
     * @param Client $client
     * @throws ClientException
     * @throws HttpClientException
     * @depends testChangeLocale
     */
    public function testGetDrivers(Client $client)
    {
        $parkId = IndexTest::PARK_ID;
        $driversListData = $client->getDrivers($parkId);
        //$expectedDriversListData = $this->getExpectedDriversListData();


        $this->assertArrayHasKey('status', $driversListData);
        $this->assertEquals(200, $driversListData['status']);

        $this->assertArrayHasKey('success', $driversListData);
        $this->assertTrue($driversListData['success']);

        $this->assertArrayHasKey('data', $driversListData);
        $this->assertIsArray($driversListData['data']);
        $data = $driversListData['data'];
        $this->assertArrayHasKey('driver_profiles', $data);
        $this->assertArrayHasKey('aggregate', $data);

        $this->assertArrayHasKey('total', $driversListData);
        $this->assertIsInt($driversListData['total']);

        $this->assertArrayHasKey('link_drivers_and_orders', $driversListData);
        $this->assertArrayHasKey('show', $driversListData);
    }

    /**
     * @param Client $client
     * @throws ClientException
     * @throws HttpClientException
     * @depends testChangeLocale
     */
    public function testCreateDriver(Client $client)
    {
        $parkId = IndexTest::PARK_ID;
        $driverPostData = $this->getTestDriverPostData();

        $driverId = $client->createDriver($parkId, $driverPostData);
        $this->assertIsString($driverId);
    }

    private function getTestDriverPostData()
    {
        $driverPostData = FixtureInterface::TEST_DRIVER_DATA;

        $driverPostData['driver_profile']['driver_license']['number'] = $this->generateDriverLicenceNumber();
        $driverPostData['driver_profile']['phones'] = [$this->generatePhoneNumber()];
        $driverPostData['driver_profile']['hire_date'] = date('Y-d-m');

        return $driverPostData;
    }

    /**
     * @param Client $client
     * @throws ClientException
     * @throws HttpClientException
     * @depends testChangeLocale
     */
    public function testGetVehiclesCardData(Client $client)
    {
        $parkId = IndexTest::PARK_ID;
        $data = $client->getVehiclesCardData($parkId);
        $this->assertIsArray($data);
        $this->validateJsonResponseData($data);
    }

    /**
     * @param Client $client
     * @throws ClientException
     * @throws HttpClientException
     * @depends testChangeLocale
     */
    public function testGetVehiclesCardModels(Client $client)
    {
        $brandName = self::BRAND_NAME;
        $data = $client->getVehiclesCardModels($brandName);
        $this->assertIsArray($data);
        $this->validateJsonResponseData($data);
    }

    /**
     * @param Client $client
     * @throws ClientException
     * @throws HttpClientException
     * @depends testChangeLocale
     */
    public function testStoreVehicles(Client $client)
    {
        $vehiclePostData = [
            'status' => 'working',
            'brand' => 'Alfa Romeo',
            'model' => '105/115',
            'color' => 'Белый',
            'year' => 1996,
            'number' => '1111112',
            'callsign' => 'тест',
            'vin' => '1C3EL46U91N594161',
            'registration_cert' => '1111111',
            'booster_count' => 2,
            'categories' =>
                [
                    0 => 'minivan',
                ],
            'carrier_permit_owner_id' => NULL,
            'transmission' => 'unknown',
            'rental' => false,
            'chairs' =>
                [
                    0 =>
                        [
                            'brand' => 'Еду-еду',
                            'categories' =>
                                [
                                    0 => 'Category2',
                                ],
                            'isofix' => true,
                        ],
                ],
            'permit' => '777777',
            'tariffs' =>
                [
                    0 => 'Эконом',
                ],
            'cargo_loaders' => 1,
            'carrying_capacity' => 300,
            'chassis' => '234',
            'park_id' => '8d40b7c41af544afa0499b9d0bdf2430',
            'amenities' =>
                [
                    0 => 'conditioner',
                    1 => 'child_seat',
                    2 => 'delivery',
                    3 => 'smoking',
                    4 => 'woman_driver',
                    5 => 'sticker',
                    6 => 'charge',
                ],
            'cargo_hold_dimensions' =>
                [
                    'length' => 150,
                    'width' => 100,
                    'height' => 50,
                ],
            'log_time' => 350,
        ];

        $data = $client->storeVehicles($vehiclePostData);
        $this->assertIsArray($data);
        $this->validateJsonResponseData($data);
    }

    /**
     * @param Client $client
     * @throws ClientException
     * @throws HttpClientException
     * @depends testChangeLocale
     */
    public function testBindDriverWithCar(Client $client)
    {
        $parkId = IndexTest::PARK_ID;
        $driverId = self::DRIVER_ID;
        $carId = self::CAR_ID;
        $data = $client->bindDriverWithCar($parkId, $driverId, $carId);
        $this->assertIsArray($data);
        $this->assertEquals('success' ,$data['status']);
    }

    private function validateJsonResponseData(array $data)
    {
        $this->assertEquals(200, $data['status']);
        $this->assertTrue($data['success']);
    }

    private function generateDriverLicenceNumber()
    {
        return $this->generateNumbersString(10);
    }

    private function generateNumbersString($size)
    {
        $ret = '';

        for ($i=0; $i<$size; $i++) {
            $ret .= rand(0, 9);
        }

        return $ret;
    }

    private function generatePhoneNumber()
    {
        $numbers = $this->generateNumbersString(12);

        return '+'.$numbers;
    }
}
