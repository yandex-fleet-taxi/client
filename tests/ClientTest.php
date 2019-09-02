<?php
declare(strict_types=1);

namespace Likemusic\YandexFleetTaxiClient\Tests;

use Http\Client\Curl\Client as CurlClient;
use Http\Client\Exception as HttpClientException;
use Http\Discovery\Psr17FactoryDiscovery;
use Likemusic\YandexFleetTaxiClient\Client;
use Likemusic\YandexFleetTaxiClient\Contracts\LocaleInterface;
use Likemusic\YandexFleetTaxiClient\Exception as ClientException;
use Likemusic\YandexFleetTaxiClient\PageParser\FleetTaxiYandexRu\Index as DashboardPageParser;
use Likemusic\YandexFleetTaxiClient\PageParser\PassportYandexRu\Auth\Welcome as WelcomePageParser;
use Likemusic\YandexFleetTaxiClient\Tests\PageParser\FleetTaxiYandexRu\IndexTest;
use PHPUnit\Framework\TestCase;

final class ClientTest extends TestCase
{
    const BRAND_NAME = 'Alfa Romeo';

    /**
     * @return Client
     * @throws ClientException
     * @throws HttpClientException
     * @doesNotPerformAssertions
     */
    public function testLogin()
    {
//        $httpClient = HttpClientDiscovery::find();
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
        $dashboardPageData = $client->changeLanguage(LocaleInterface::RUSSIAN);
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

    private function getExpectedDriversListData()
    {
        $json = file_get_contents(__DIR__ . '/Textures/Pages/fleet.taxi.yandex.ru/drivers/list.json');

        return json_decode($json, true);
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

        $driverPostData = [
            'accounts' =>
                [
                    'balance_limit' => '5',
                ],
            'driver_profile' =>
                [
                    'driver_license' =>
                        [
                            'country' => 'rus',
                            'number' => $this->generateDriverLicenceNumber(),
                            'expiration_date' => '2019-09-20',
                            'issue_date' => '2019-09-01',
                            'birth_date' => NULL,
                        ],
                    'first_name' => 'Валерий',
                    'last_name' => 'Иващенко',
                    'middle_name' => 'Игроевич',
                    'phones' =>
                        [
                            0 => $this->generatePhoneNumber(),
                        ],
                    'work_status' => 'working',
                    'work_rule_id' => 'a6cb3fbe61a54ba28f8f8b5e35b286db',
                    'providers' =>
                        [
                            0 => 'yandex',
                        ],
                    'hire_date' => '2019-09-01',
                    'deaf' => NULL,
                    'email' => NULL,
                    'address' => NULL,
                    'comment' => NULL,
                    'check_message' => NULL,
                    'car_id' => NULL,
                    'fire_date' => NULL,
                ],
        ];

        $driverId = $client->createDriver($parkId, $driverPostData);
        $this->assertIsString($driverId);
    }

    /**
     * @param Client $client
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
     * @depends testChangeLocale
     */
    public function testGetVehiclesCardModels(Client $client)
    {
        $brandName = self::BRAND_NAME;
        $data = $client->getVehiclesCardModels($brandName);
        $this->assertIsArray($data);
        $this->validateJsonResponseData($data);
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
