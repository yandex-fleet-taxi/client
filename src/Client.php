<?php

namespace Likemusic\YandexFleetTaxiClient;

use Http\Client\Common\Plugin\CookiePlugin;
use Http\Client\Common\PluginClient;
use Http\Client\Exception as HttpClientException;
use Http\Client\HttpClient;
use Http\Message\CookieJar;
use Likemusic\YandexFleetTaxiClient\Contracts\ClientInterface;
use Likemusic\YandexFleetTaxiClient\Contracts\HttpMethodInterface;
use Likemusic\YandexFleetTaxiClient\PageParser\FleetTaxiYandexRu\Index as DashboardPageParser;
use Likemusic\YandexFleetTaxiClient\PageParser\PassportYandexRu\Auth\Welcome as WelcomePageParser;
use Likemusic\YandexFleetTaxiClient\PageParser\PassportYandexRu\Auth\Welcome\Data as WelcomePageParserData;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class Client implements ClientInterface
{
    const CONTENT_TYPE_JSON = 'application/json; charset=utf-8';

    /**
     * @var HttpClient
     */
    private $httpPluginClient;

    /**
     * @var RequestFactoryInterface
     */
    private $requestFactory;

    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;
    //private $uriFactory;

    /**
     * @var WelcomePageParser
     */
    private $welcomePageParser;

    /**
     * @var DashboardPageParser
     */
    private $dashboardPageParser;

    /**
     * @var string
     */
    private $csrfToken;

    /**
     *
     * @param HttpClient $httpClient
     * @param RequestFactoryInterface $requestFactory
     * @param StreamFactoryInterface $streamFactory
     * @param WelcomePageParser $welcomePageParser
     * @param DashboardPageParser $dashboardPageParser
     */
    public function __construct(
        HttpClient $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        WelcomePageParser $welcomePageParser,
        DashboardPageParser $dashboardPageParser
        //UriFactory $uriFactory
    )
    {
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
        //$this->uriFactory = $uriFactory;
        $this->welcomePageParser = $welcomePageParser;

        $cookiePlugin = new CookiePlugin(new CookieJar());

        $pluginClient = new PluginClient(
            $httpClient,
            [$cookiePlugin],
            );

        $this->httpPluginClient = $pluginClient;
        $this->dashboardPageParser = $dashboardPageParser;
    }

    /**
     * @param string $login
     * @param string $password
     * @throws Exception
     * @throws HttpClientException
     */
    public function login(string $login, string $password)
    {
        $passportPageResponse = $this->getPassportPage();

        $welcomePageParserData = $this->getDataFromPassportPageResponse($passportPageResponse);

        $csrfToken = $welcomePageParserData->getCsrfToken();
        $processUuid = $welcomePageParserData->getProcessUuid();
        $retPath = 'https://fleet.taxi.yandex.ru';

        $loginPageResponse = $this->submitLogin($login, $csrfToken, $processUuid, $retPath);
        list($tackId, $newCsrfToken) = $this->getDataFromLoginPageResponse($loginPageResponse);
        $this->csrfToken = $csrfToken;

        $this->submitPassword($csrfToken, $tackId, $password);
    }

    /**
     * @return ResponseInterface
     * @throws Exception
     * @throws HttpClientException
     */
    private function getPassportPage()
    {
        $url = 'https://passport.yandex.ru/auth/welcome?retpath=https%3A%2F%2Ffleet.taxi.yandex.ru';
        return $this->sendGetRequestAndValidateResponse($url);
    }

    /**
     * @param string $url
     * @return ResponseInterface
     * @throws HttpClientException
     * @throws HttpJsonResponseException
     * @throws HttpResponseException
     */
    private function sendGetRequestAndValidateResponse(string $url): ResponseInterface
    {
        $request = $this->createGetRequest($url);

        return $this->sendRequestAndValidateResponse($request);
    }

    /**
     * @param $httpMethod
     * @param $uri
     * @return RequestInterface
     */
    private function createRequest($httpMethod, $uri): RequestInterface
    {
        return $this->requestFactory->createRequest(
            $httpMethod,
            $uri
        );
    }

    /**
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws HttpClientException
     */
    private function sendRequest(RequestInterface $request): ResponseInterface
    {
        return $this->httpPluginClient->sendRequest($request);
    }

    /**
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws HttpClientException
     * @throws HttpJsonResponseException
     * @throws HttpResponseException
     */
    private function sendRequestAndValidateResponse(RequestInterface $request): ResponseInterface
    {
        $response = $this->httpPluginClient->sendRequest($request);
        $this->validateResponse($response, $request);

        return $response;
    }

    /**
     * @param ResponseInterface $response
     * @param RequestInterface|null $request
     * @throws HttpJsonResponseException
     * @throws HttpResponseException
     */
    private function validateResponse(ResponseInterface $response, RequestInterface $request = null)
    {
        if (($responseStatusCode = $response->getStatusCode()) !== 200) {
            $errorCode = $responseStatusCode;
            $errorMessage = $this->getResponseBodyText($response);
            $errorReasonPhrase = $response->getReasonPhrase();

            $isJson = $this->isJsonResponse($response);

            if ($isJson) {
                $responseArray = $this->jsonDecode($errorMessage);
                $responseCode = $responseArray['code'];
                $responseMessage = $responseArray['message'];
                $responseDetails = $responseArray['details'];

                throw new HttpJsonResponseException($errorMessage, $errorCode, $errorReasonPhrase, $responseCode, $responseMessage, $responseDetails);
            } else {
                throw new HttpResponseException($errorMessage, $errorCode, $errorReasonPhrase);
            }
        }
    }

    private function isJsonResponse(ResponseInterface $response)
    {
        $contentType = $this->getResponseContentType($response);

        return $contentType == self::CONTENT_TYPE_JSON;
    }

    private function getResponseContentType(ResponseInterface $response)
    {
        return $response->getHeader('Content-Type')[0];
    }

    private function getDataFromPassportPageResponse(ResponseInterface $response)
    {
        $bodyStream = $response->getBody();
        $body = $bodyStream->getContents();

        return $this->getVarsFromPassportPage($body);
    }

    private function getVarsFromPassportPage(string $body): WelcomePageParserData
    {
        return $this->welcomePageParser->getData($body);
    }


    /**
     * @param string $login
     * @param string $csrfToken
     * @param string $processUuid
     * @param string $retPath
     * @return ResponseInterface
     * @throws Exception
     * @throws HttpClientException
     */
    private function submitLogin(string $login, string $csrfToken, string $processUuid, string $retPath)
    {
        $uri = 'https://passport.yandex.ru/registration-validations/auth/multi_step/start';
        $postData = [
            'csrf_token' => $csrfToken,
            'login' => $login,
            'process_uuid' => $processUuid,
            'retpath' => $retPath,
        ];

        return $this->sendPostUrlEncodedRequestAndValidateResponse($uri, $postData);
    }

    /**
     * @param string $uri
     * @param array $postData
     * @return ResponseInterface
     * @throws HttpClientException
     * @throws HttpJsonResponseException
     * @throws HttpResponseException
     */
    private function sendPostUrlEncodedRequestAndValidateResponse(string $uri, array $postData = [])
    {
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        $body = http_build_query($postData);

        $stream = $this->streamFactory->createStream($body);

        return $this->sendPostRequestAndValidateResponse($uri, $stream, $headers);
    }

    /**
     * @param string $uri
     * @param StreamInterface $body
     * @param array $headers
     * @return ResponseInterface
     * @throws HttpClientException
     * @throws HttpJsonResponseException
     * @throws HttpResponseException
     */
    private function sendPostRequestAndValidateResponse(string $uri, StreamInterface $body = null, $headers = [])
    {
        $request = $this->createPostRequest($uri, $headers, $body);

        return $this->sendRequestAndValidateResponse($request);
    }

    /**
     * @param string $uri
     * @param StreamInterface $body
     * @param array $headers
     * @return ResponseInterface
     * @throws HttpClientException
     * @throws HttpJsonResponseException
     * @throws HttpResponseException
     */
    private function sendPutRequestAndValidateResponse(string $uri, StreamInterface $body = null, $headers = [])
    {
        $request = $this->createPutRequest($uri, $headers, $body);

        return $this->sendRequestAndValidateResponse($request);
    }

    private function createPostRequest($uri, $headers = [], StreamInterface $body = null): RequestInterface
    {
        $request = $this->createRequest(HttpMethodInterface::POST, $uri);

        return $this->modifyRequestByHeadersAndBody($request, $headers, $body);
    }

    private function modifyRequestByHeadersAndBody(RequestInterface $request, $headers = [], StreamInterface $body = null)
    {
        if ($headers) {
            $request = $this->addHeaders($request, $headers);
        }

        if ($body) {
            $request = $request->withBody($body);
        }

        return $request;
    }

    private function createPutRequest($uri, $headers = [], StreamInterface $body = null): RequestInterface
    {
        $request = $this->createRequest(HttpMethodInterface::PUT, $uri);

        return $this->modifyRequestByHeadersAndBody($request, $headers, $body);
    }

    private function createGetRequest($uri, $headers = [], StreamInterface $body = null): RequestInterface
    {
        $request = $this->createRequest(HttpMethodInterface::GET, $uri);
        return $this->modifyRequestByHeadersAndBody($request, $headers, $body);
    }

    private function addHeaders(RequestInterface $request, array $headers): RequestInterface
    {
        foreach ($headers as $key => $value) {
            $request = $request->withHeader($key, $value);
        }

        return $request;
    }

    private function getDataFromLoginPageResponse(ResponseInterface $response)
    {
        $body = $response->getBody()->getContents();

        return $this->getDataFromLoginPage($body);
    }

    private function getDataFromLoginPage(string $json)
    {
        $data = json_decode($json, true);

        return [$data['track_id'], $data['csrf_token']];
    }

    /**
     * @param string $csrfToken
     * @param string $trackId
     * @param string $password
     * @return ResponseInterface
     * @throws Exception
     * @throws HttpClientException
     */
    private function submitPassword(string $csrfToken, string $trackId, string $password)
    {
        $uri = 'https://passport.yandex.ru/registration-validations/auth/multi_step/commit_password';

        $postData = [
            'csrf_token' => $csrfToken,
            'track_id' => $trackId,
            'password' => $password,
        ];

        $response = $this->sendPostUrlEncodedRequestAndValidateResponse($uri, $postData);

        $this->validatePasswordResponse($response);

        return $response;
    }

    /**
     * @param ResponseInterface $response
     * @throws Exception
     */
    private function validatePasswordResponse(ResponseInterface $response)
    {
        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);

        if ($data['status'] !== 'ok') {
            throw new Exception("Bad status ({$data['status']})for Password page. Body: " . $body);
        }
    }

    /**
     * @return array
     * @throws Exception
     * @throws HttpClientException
     */
    public function getDashboardPageData()
    {
        $dashboardResponse = $this->getDashboardPage();

        $this->updateCsrfToken($dashboardResponse);
        return $this->getDataFromDashboardPageResponse($dashboardResponse);
    }

    /**
     * @return ResponseInterface
     * @throws Exception
     * @throws HttpClientException
     */
    private function getDashboardPage()
    {
        $url = 'https://fleet.taxi.yandex.ru/';

        return $this->sendGetRequestAndValidateResponse($url);
    }

    private function updateCsrfToken(ResponseInterface $response)
    {
        $this->csrfToken = $this->getCsrfTokenByResponse($response);
    }

    private function getCsrfTokenByResponse(ResponseInterface $response)
    {
        return $response->getHeader('X-CSRF-TOKEN')[0];
    }

    private function getDataFromDashboardPageResponse(ResponseInterface $dashboardResponse): array
    {
        $body = $this->getResponseBodyText($dashboardResponse);

        return $this->getDataFromDashboardPage($body);
    }

    private function getResponseBodyText(ResponseInterface $response): string
    {
        return $response->getBody()->getContents();
    }

    private function getDataFromDashboardPage(string $html): array
    {
        return $this->dashboardPageParser->getData($html);
    }

    /**
     * @param string $languageCode
     * @return array
     * @throws Exception
     * @throws HttpClientException
     */
    public function changeLanguage(string $languageCode): array
    {
        $response = $this->getDashboardPageWithLanguage($languageCode);

        return $this->getDataFromDashboardPageResponse($response);
    }

    /**
     * @param string $languageCode
     * @return ResponseInterface
     * @throws Exception
     * @throws HttpClientException
     */
    private function getDashboardPageWithLanguage(string $languageCode)
    {
        $url = 'https://fleet.taxi.yandex.ru/?lang=' . $languageCode;

        return $this->sendGetRequestAndValidateResponse($url);
    }

    /**
     * @param string $parkId
     * @param int $limit
     * @param int $page
     * @param array $carAmenities
     * @param array $carCategories
     * @param null $status
     * @param string $text
     * @param int $workRuleId
     * @param string $workStatusId
     * @param array $sort
     * @return array
     * @throws Exception
     * @throws HttpClientException
     */
    public function getDrivers(
        string $parkId,
        int $limit = 25,
        int $page = 1,
        array $carAmenities = [],
        array $carCategories = [],
        $status = null,
        string $text = '',
        int $workRuleId = null,
        string $workStatusId = 'working',
        array $sort = [
            [
                'direction' => "asc",
                'field' => 'car.call_sign',
            ]
        ]
    ): array
    {
        $uri = 'https://fleet.taxi.yandex.ru/drivers/list';

        $postData = [
            'car_amenities' => $carAmenities,
            'car_categories' => $carCategories,
            'limit' => $limit,
            'page' => $page,
            'park_id' => $parkId,
            'sort' => $sort,
            'status' => $status,
            'text' => $text,
            'work_rule_id' => $workRuleId,
            'work_status_id' => $workStatusId,
        ];

        $headers = [
            'X-CSRF-TOKEN' => $this->csrfToken,
        ];

        $response = $this->sendPostJsonEncodedRequestAndValidateResponse($uri, $postData, $headers);

        return $this->getJsonDecodedBody($response);
    }

    private function getJsonDecodedBody(ResponseInterface $response)
    {
        $json = $this->getResponseBodyText($response);

        return $this->jsonDecode($json);
    }

    /**
     * @param string $uri
     * @param array $postData
     * @param array $headers
     * @return ResponseInterface
     * @throws HttpClientException
     */
    private function sendPostJsonEncodedRequestAndValidateResponse(string $uri, array $postData = [], $headers = [])
    {
        $headers['Content-Type'] = 'application/json;charset=UTF-8';

        $body = json_encode($postData);
        $stream = $this->streamFactory->createStream($body);

        return $this->sendPostRequestAndValidateResponse($uri, $stream, $headers);
    }

    /**
     * @param string $uri
     * @param array $postData
     * @param array $headers
     * @return ResponseInterface
     * @throws HttpClientException
     */
    private function sendPutJsonEncodedRequestAndValidateResponse(string $uri, array $postData = [], $headers = [])
    {
        $headers['Content-Type'] = 'application/json;charset=UTF-8';

        $body = json_encode($postData);
        $stream = $this->streamFactory->createStream($body);

        return $this->sendPutRequestAndValidateResponse($uri, $stream, $headers);
    }

    private function jsonDecode($json): array
    {
        return json_decode($json, true);
    }

    /**
     * @param string $parkId
     * @param array $postData
     * @return string Created driver id
     * @throws Exception
     * @throws HttpClientException
     */
    public function createDriver(string $parkId, array $postData): string
    {
        $uri = 'https://fleet.taxi.yandex.ru/api/v1/drivers/create';

        $headers = [
            'X-CSRF-TOKEN' => $this->csrfToken,
            'X-Park-Id' => $parkId,
        ];

        $response = $this->sendPostJsonEncodedRequestAndValidateResponse($uri, $postData, $headers);

        $data = $this->getJsonDecodedBody($response);

        return $data['id'];
    }

    /**
     * @param string $parkId
     * @return array
     * @throws Exception
     * @throws HttpClientException
     */
    public function getVehiclesCardData(string $parkId)
    {
        $uri = 'https://fleet.taxi.yandex.ru/vehicles/card/data';

        $headers = [
            'X-CSRF-TOKEN' => $this->csrfToken,
        ];

        $postData = [
            'park_id' => $parkId,
        ];

        $response = $this->sendPostJsonEncodedRequestAndValidateResponse($uri, $postData, $headers);
        $this->updateCsrfToken($response);

        return $this->getJsonDecodedBody($response);
    }

    /**
     * @param string $brandName
     * @return array
     * @throws Exception
     * @throws HttpClientException
     */
    public function getVehiclesCardModels(string $brandName)
    {
        $uri = 'https://fleet.taxi.yandex.ru/vehicles/card/models';

        $headers = [
            'X-CSRF-TOKEN' => $this->csrfToken,
        ];

        $postData = [
            'brand_name' => $brandName,
        ];

        $response = $this->sendPostJsonEncodedRequestAndValidateResponse($uri, $postData, $headers);
        $this->updateCsrfToken($response);

        return $this->getJsonDecodedBody($response);
    }

    /**
     * @param array $postData
     * @return array
     * @throws Exception
     * @throws HttpClientException
     */
    public function storeVehicles(array $postData)
    {
        $uri = 'https://fleet.taxi.yandex.ru/vehicles/store';

        $headers = [
            'X-CSRF-TOKEN' => $this->csrfToken,
        ];

        $response = $this->sendPostJsonEncodedRequestAndValidateResponse($uri, $postData, $headers);
        $this->updateCsrfToken($response);

        return $this->getJsonDecodedBody($response);
    }

    /**
     * @param string $parkId
     * @param string $driverId
     * @param string $carId
     * @return array
     * @throws Exception
     * @throws HttpClientException
     */
    public function bindDriverWithCar(string $parkId, string $driverId, string $carId)
    {
        $uri = 'https://fleet.taxi.yandex.ru/api/v1/drivers/car-bindings';

        $headers = [
            'X-CSRF-TOKEN' => $this->csrfToken,
            'X-Park-Id' => $parkId,
        ];

        $postData = [
            'driver_id' => $driverId,
            'car_id' => $carId,
        ];

        $response = $this->sendPutJsonEncodedRequestAndValidateResponse($uri, $postData, $headers);

        return $this->getJsonDecodedBody($response);
    }
}
