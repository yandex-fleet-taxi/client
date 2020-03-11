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
use PHPUnit\Framework\TestCase;

final class ClientTest extends TestCase
{
    const CONFIG_FILENAME = 'tests/ClientTest.json';
    const CONFIG_COMMON_FILENAME = 'tests/ClientTest.Common.json';
    const EXPECTED_DATA_COMMON_FILENAME = 'tests/ClientTest.Expected.Common.json';
    const EXPECTED_DATA_PARK_FILENAME_TEMPLATE = 'tests/ClientTest.Expected.{parkId}.json';

    /**
     * @return Client
     * @throws ClientException
     * @throws HttpClientException
     * @doesNotPerformAssertions
     * @group get
     */
    public function testLogin()
    {
        $curlOptions = $this->getCurlOptions();

        $httpClient = new CurlClient(null, null, $curlOptions);
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

        $testConfig = $this->getTestConfig();
        $login = $testConfig['login'];
        $password = $testConfig['password'];

        $client->login($login, $password);

        return $client;
    }

    /**
     * @param array $testConfig
     * @return array
     */
    private function getCurlOptions()
    {
        $testConfigCommon = $this->getTestConfigCommon();

        $configCurlOptions = $testConfigCommon['curl_options'];

        return [
            CURLOPT_PROXY => $configCurlOptions['proxy'],
            CURLOPT_SSL_VERIFYHOST => $configCurlOptions['verifyhost'],
            CURLOPT_SSL_VERIFYPEER => $configCurlOptions['verifypeer'],
        ];
    }

    /**
     * @return array
     */
    private function getTestConfigCommon()
    {
        $configJson = file_get_contents(self::CONFIG_FILENAME);

        return json_decode($configJson, true);
    }

    /**
     * @return array
     */
    private function getTestConfig()
    {
        $configJson = file_get_contents(self::CONFIG_FILENAME);

        return json_decode($configJson, true);
    }

    /**
     * @param Client $client
     * @return Client
     * @throws HttpClientException
     * @throws ClientException
     * @depends testLogin
     * @group get
     */
    public function testGetDashboardPageData(Client $client)
    {
        $data = $client->getDashboardPageData();
        $expectedDashboardDataLandDefault = $this->getExpectedDashboardDataLandDefault();
        $this->assertEquals($expectedDashboardDataLandDefault, $data);

        return $client;
    }

    /**
     * @return array
     */
    private function getExpectedDashboardDataLandDefault()
    {
        $testConfig = $this->getExpectedDataPark();

        return $testConfig['dashboard']['lang_default'];
    }

    /**
     * @return array
     */
    private function getExpectedDataPark()
    {
        $expectedDataParkFilename = $this->getExpectedDataParkFilename();
        $configJson = file_get_contents($expectedDataParkFilename);

        return json_decode($configJson, true);
    }

    private function getExpectedDataParkFilename()
    {
        $parkId = $this->getTestParkId();

        return str_replace('{parkId}', $parkId, self::EXPECTED_DATA_PARK_FILENAME_TEMPLATE);
    }

    /**
     * @return string
     */
    private function getTestParkId()
    {
        $testConfig = $this->getTestConfig();

        return $testConfig['park_id'];
    }

    /**
     * @param Client $client
     * @return Client
     * @throws ClientException
     * @throws HttpClientException
     * @depends testGetDashboardPageData
     * @group get
     */
    public function testChangeLocale(Client $client): Client
    {
        $data = $client->changeLanguage(LanguageInterface::RUSSIAN);
        $expectedDashboardData = $this->getExpectedDashboardDataLandRussian();
        $this->assertEquals($expectedDashboardData, $data);

        return $client;
    }

    /**
     * @return array
     */
    private function getExpectedDashboardDataLandRussian()
    {
        $testConfig = $this->getExpectedDataPark();

        return $testConfig['dashboard']['lang_russian'];
    }

    /**
     * @param Client $client
     * @throws ClientException
     * @throws HttpClientException
     * @depends testChangeLocale
     * @group get
     */
    public function testGetDrivers(Client $client)
    {
        $parkId = $this->getTestParkId();
        $driversListData = $client->getDrivers($parkId);

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
        $parkId = $this->getTestParkId();
        $driverPostData = $this->getTestDriverPostData();

        $driverId = $client->createDriver($parkId, $driverPostData);
        $this->assertIsString($driverId);
        $this->assertEquals(32, strlen($driverId));
    }

    private function getTestDriverPostData()
    {
        $driverPostData = FixtureInterface::TEST_DRIVER_DATA;

        $driverPostData['driver_profile']['driver_license']['number'] = $this->generateDriverLicenceNumber();
        $driverPostData['driver_profile']['phones'] = [$this->generatePhoneNumber()];
        $driverPostData['driver_profile']['hire_date'] = date('Y-m-d');
        $driverPostData['driver_profile']['work_rule_id'] = $this->getTestWorkRuleId();

        return $driverPostData;
    }

    private function generateDriverLicenceNumber()
    {
        return $this->generateNumbersString(10);
    }

    private function generateNumbersString($size)
    {
        $ret = '';

        for ($i = 0; $i < $size; $i++) {
            $ret .= rand(0, 9);
        }

        return $ret;
    }

    private function generatePhoneNumber()
    {
        $numbers = $this->generateNumbersString(12);

        return '+' . $numbers;
    }

    private function getTestWorkRuleId()
    {
        $testConfig = $this->getTestConfig();

        return $testConfig['work_rule_id'];
    }

    /**
     * @param Client $client
     * @throws ClientException
     * @throws HttpClientException
     * @depends testChangeLocale
     * @group get
     */
    public function testGetVehiclesCardData(Client $client)
    {
        $parkId = $this->getTestParkId();
        $data = $client->getVehiclesCardData($parkId);
        $this->assertIsArray($data);
        $this->validateJsonResponseData($data);

        $expectedVehiclesCardData = $this->getExpectedVehiclesCardData();
        $this->assertEquals($expectedVehiclesCardData, $data);
    }

    private function validateJsonResponseData(array $data)
    {
        $this->assertEquals(200, $data['status']);
        $this->assertTrue($data['success']);
    }

    /**
     * @return array
     */
    private function getExpectedVehiclesCardData()
    {
        $expectedData = $this->getExpectedDataPark();

        return $expectedData['vehicles_card_data'];
    }

    /**
     * @param Client $client
     * @throws ClientException
     * @throws HttpClientException
     * @depends testChangeLocale
     * @group get
     */
    public function testGetVehiclesCardModels(Client $client)
    {
        $brandName = $this->getConfigBrandName();
        $data = $client->getVehiclesCardModels($brandName);
        $this->assertIsArray($data);
        $this->validateJsonResponseData($data);

        $expectedVehiclesCardModels = $this->getExpectedVehiclesCardModels();
        $this->assertEquals($expectedVehiclesCardModels, $data);
    }

    /**
     * @return string
     */
    private function getConfigBrandName()
    {
        $testConfig = $this->getTestConfigCommon();

        return $testConfig['brand_name'];
    }

    /**
     * @return array
     */
    private function getExpectedVehiclesCardModels()
    {
        $expectedData = $this->getExpectedDataCommon();

        return $expectedData['vehicles_card_models'];
    }

    private function getExpectedDataCommon()
    {
        $configJson = file_get_contents(self::EXPECTED_DATA_COMMON_FILENAME);

        return json_decode($configJson, true);
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
        $parkId = $this->getTestParkId();
        $testConfig = $this->getTestConfig();
        $driverId = $this->getTestDriverId($testConfig);
        $carId = $this->getTestCarId($testConfig);
        $data = $client->bindDriverWithCar($parkId, $driverId, $carId);
        $this->assertIsArray($data);
        $this->assertEquals('success', $data['status']);
    }

    /**
     * @param array $testConfig
     * @return string
     */
    private function getTestDriverId(array $testConfig)
    {
        return $testConfig['driver_id'];
    }

    private function getTestCarId(array $testConfig)
    {
        return $testConfig['car_id'];
    }

    /**
     * @param Client $client
     * @throws ClientException
     * @throws HttpClientException
     * @depends testChangeLocale
     * @group get
     */
    public function testGetDriversCardData(Client $client)
    {
        $parkId = $this->getTestParkId();
        $data = $client->getDriversCardData($parkId);
        $this->assertIsArray($data);
        $this->validateJsonResponseData($data);
        $expectedDriversCardData = $this->getExpectedDriversCardData();

        $this->assertEquals($expectedDriversCardData, $data);
    }

    private function getExpectedDriversCardData()
    {
        $expectedData = $this->getExpectedDataPark();

        return $expectedData['drivers_card_data'];
    }
}
