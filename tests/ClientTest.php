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
        $driverPostData['driver_profile']['hire_date'] = date('Y-m-d');

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

    private function validateJsonResponseData(array $data)
    {
        $this->assertEquals(200, $data['status']);
        $this->assertTrue($data['success']);
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
        $this->assertEquals('success', $data['status']);
    }

    /**
     * @param Client $client
     * @throws ClientException
     * @throws HttpClientException
     * @depends testChangeLocale
     */
    public function testGetDriversCardData(Client $client)
    {
        $parkId = IndexTest::PARK_ID;
        $data = $client->getDriversCardData($parkId);
        $this->assertIsArray($data);
        $this->validateJsonResponseData($data);
        $expectedDriversCardData = $this->getExpectedDriversCardData();

        $this->assertEquals($expectedDriversCardData, $data);
    }

    private function getExpectedDriversCardData()
    {
        return [
            'status' => 200,
            'success' => true,
            'data' =>
                [
                    'references' =>
                        [
                            'driver_statuses' =>
                                [
                                    0 =>
                                        [
                                            'id' => 'not_working',
                                            'name' => 'Не работает',
                                        ],
                                    1 =>
                                        [
                                            'id' => 'working',
                                            'name' => 'Работает',
                                        ],
                                    2 =>
                                        [
                                            'id' => 'fired',
                                            'name' => 'Уволен',
                                        ],
                                ],
                            'driver_identification_types' =>
                                [
                                    0 =>
                                        [
                                            'id' => 'national',
                                            'name' => 'Национальный паспорт',
                                        ],
                                    1 =>
                                        [
                                            'id' => 'passport',
                                            'name' => 'Международный паспорт',
                                        ],
                                ],
                            'countries' =>
                                [
                                    0 =>
                                        [
                                            'code' => 'arm',
                                            'name_en' => 'Armenia',
                                            'name_ru' => 'Армения',
                                        ],
                                    1 =>
                                        [
                                            'code' => 'aze',
                                            'name_en' => 'Azerbaijan',
                                            'name_ru' => 'Азербайджан',
                                        ],
                                    2 =>
                                        [
                                            'code' => 'blr',
                                            'name_en' => 'Belarus',
                                            'name_ru' => 'Беларусь',
                                        ],
                                    3 =>
                                        [
                                            'code' => 'est',
                                            'name_en' => 'Estonia',
                                            'name_ru' => 'Эстония',
                                        ],
                                    4 =>
                                        [
                                            'code' => 'fin',
                                            'name_en' => 'Finland',
                                            'name_ru' => 'Финляндия',
                                        ],
                                    5 =>
                                        [
                                            'code' => 'geo',
                                            'name_en' => 'Georgia',
                                            'name_ru' => 'Грузия',
                                        ],
                                    6 =>
                                        [
                                            'code' => 'gha',
                                            'name_en' => 'Ghana',
                                            'name_ru' => 'Гана',
                                        ],
                                    7 =>
                                        [
                                            'code' => 'isr',
                                            'name_en' => 'Israel',
                                            'name_ru' => 'Израиль',
                                        ],
                                    8 =>
                                        [
                                            'code' => 'civ',
                                            'name_en' => 'Ivory Coast',
                                            'name_ru' => 'Кот-Д’Ивуар',
                                        ],
                                    9 =>
                                        [
                                            'code' => 'kaz',
                                            'name_en' => 'Kazakhstan',
                                            'name_ru' => 'Казахстан',
                                        ],
                                    10 =>
                                        [
                                            'code' => 'kgz',
                                            'name_en' => 'Kyrgyzstan',
                                            'name_ru' => 'Киргизия',
                                        ],
                                    11 =>
                                        [
                                            'code' => 'lva',
                                            'name_en' => 'Latvia',
                                            'name_ru' => 'Латвия',
                                        ],
                                    12 =>
                                        [
                                            'code' => 'ltu',
                                            'name_en' => 'Lithuania',
                                            'name_ru' => 'Литва',
                                        ],
                                    13 =>
                                        [
                                            'code' => 'mda',
                                            'name_en' => 'Moldova',
                                            'name_ru' => 'Молдова',
                                        ],
                                    14 =>
                                        [
                                            'code' => 'rou',
                                            'name_en' => 'Romania',
                                            'name_ru' => 'Румыния',
                                        ],
                                    15 =>
                                        [
                                            'code' => 'rus',
                                            'name_en' => 'Russia',
                                            'name_ru' => 'Россия',
                                        ],
                                    16 =>
                                        [
                                            'code' => 'srb',
                                            'name_en' => 'Serbia',
                                            'name_ru' => 'Сербия',
                                        ],
                                    17 =>
                                        [
                                            'code' => 'tjk',
                                            'name_en' => 'Tajikistan',
                                            'name_ru' => 'Таджикистан',
                                        ],
                                    18 =>
                                        [
                                            'code' => 'ukr',
                                            'name_en' => 'Ukraine',
                                            'name_ru' => 'Украина',
                                        ],
                                    19 =>
                                        [
                                            'code' => 'gbr',
                                            'name_en' => 'United Kingdom',
                                            'name_ru' => 'Великобритания',
                                        ],
                                    20 =>
                                        [
                                            'code' => 'uzb',
                                            'name_en' => 'Uzbekistan',
                                            'name_ru' => 'Узбекистан',
                                        ],
                                ],
                        ],
                    'work_rules' =>
                        [
                            0 =>
                                [
                                    'id' => 'a6cb3fbe61a54ba28f8f8b5e35b286db',
                                    'name' => '!Базовый - 5',
                                    'name_localized' => '!Базовый - 5',
                                    'type' => 0,
                                    'workshift_commission_percent' => 30,
                                    'workshifts_enabled' => true,
                                    'commisison_for_subvention_percent' => 5,
                                    'enable' => true,
                                ],
                            1 =>
                                [
                                    'id' => 'e26a3cf21acfe01198d50030487e046b',
                                    'name' => 'QIWI - 4',
                                    'name_localized' => 'QIWI - 4',
                                    'type' => 0,
                                    'workshift_commission_percent' => 30,
                                    'workshifts_enabled' => true,
                                    'commisison_for_subvention_percent' => 4,
                                    'enable' => true,
                                ],
                            2 =>
                                [
                                    'id' => 'a68338d6a5534b8bb750010484d5b424',
                                    'name' => 'Простой - 3',
                                    'name_localized' => 'Простой - 3',
                                    'type' => 0,
                                    'workshift_commission_percent' => 20,
                                    'workshifts_enabled' => true,
                                    'commisison_for_subvention_percent' => 3,
                                    'enable' => true,
                                ],
                        ],
                    'required_fields' =>
                        [
                            0 => 'balance_limit',
                            1 => 'work_status',
                            2 => 'work_rule_id',
                            3 => 'providers',
                            4 => 'hire_date',
                            5 => 'first_name',
                            6 => 'last_name',
                            7 => 'phone',
                            8 => 'license_country',
                            9 => 'license_number',
                            10 => 'license_expiration_date',
                            11 => 'license_issue_date',
                        ],
                    'driver' =>
                        [
                            'disabled_fields' =>
                                [
                                    0 => 'first_name',
                                    1 => 'last_name',
                                    2 => 'middle_name',
                                    3 => 'license_country',
                                    4 => 'license_number',
                                    5 => 'license_expiration_date',
                                    6 => 'license_issue_date',
                                ],
                            'show' =>
                                [
                                    'save' => true,
                                    'hearing_impaired_driver' => false,
                                ],
                        ],
                ],
        ];//todo
    }
}
