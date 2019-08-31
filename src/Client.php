<?php

namespace Likemusic\YandexFleetTaxiClient;

use Http\Client\Exception as HttpClientException;
use Likemusic\YandexFleetTaxiClient\Contracts\ClientInterface;
//use Http\Message\RequestFactory;
use Http\Message\StreamFactory;
//use Http\Message\UriFactory;
use Likemusic\YandexFleetTaxiClient\Contracts\HttpMethodInterface;
use Http\Client\HttpClient;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Likemusic\YandexFleetTaxiClient\PageParser\PassportYandexRu\Auth\Welcome as WelcomePageParser;
use Likemusic\YandexFleetTaxiClient\PageParser\PassportYandexRu\Auth\Welcome\Data as WelcomePageParserData ;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Http\Message\CookieJar;
use Http\Client\Common\PluginClient;
use Http\Client\Common\Plugin\CookiePlugin;

class Client implements ClientInterface
{
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
     *
     * @param HttpClient $httpClient
     * @param RequestFactoryInterface $requestFactory
     * @param WelcomePageParser $welcomePageParser
     * @param StreamFactoryInterface $streamFactory
     */
    public function __construct(
        HttpClient $httpClient,
        RequestFactoryInterface $requestFactory,
        WelcomePageParser $welcomePageParser,
        StreamFactoryInterface $streamFactory
        //UriFactory $uriFactory
    ) {
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
        //$this->uriFactory = $uriFactory;
        $this->welcomePageParser = $welcomePageParser;

        $cookiePlugin = new CookiePlugin(new CookieJar());

        $pluginClient = new PluginClient(
            $httpClient,
            [$cookiePlugin]
        );

        $this->httpPluginClient = $pluginClient;

    }

    /**
     * @param string $login
     * @param string $password
     * @param bool $rememberMe
     * @throws Exception
     * @throws HttpClientException
     */
    public function login(string $login, string $password, bool $rememberMe = false)
    {
        $passportPageResponse = $this->getPassportPage();

        $welcomePageParserData = $this->getDataFromPassportPageResponse($passportPageResponse);

        $csrfToken = $welcomePageParserData->getCsrfToken();
        $processUuid = $welcomePageParserData->getProcessUuid();
        $retPath = 'https://fleet.taxi.yandex.ru';

        $loginPageResponse = $this->submitLogin($login, $csrfToken, $processUuid, $retPath);
        list($tackId) = $this->getDataFromLoginPageResponse($loginPageResponse);

        $passwordPageResponse = $this->submitPassword($csrfToken, $tackId, $password);

        $dashboardResponse = $this->getDashboardPage();

        $dashboardPageData = $this->getDataFromDashboardPageResponse($dashboardResponse);

        //$request = $this->requestFactory->createRequest(HttpMethodInterface::POST, 'http://httplug.io', [], );
    }

    private function getDataFromDashboardPageResponse(ResponseInterface $dashboardResponse)
    {
        $body = $dashboardResponse->getBody()->getContents();

        return $this->getDataFromDashboardPage($body);
    }

    private function getDataFromDashboardPage(string $html)
    {

    }

    private function getDataFromLoginPageResponse(ResponseInterface $response)
    {
        $body = $response->getBody()->getContents();

        return $this->getDataFromLoginPage($body);
    }

    private function getDataFromLoginPage(string $json)
    {
        $data = json_decode($json, true);

        return [$data['track_id']];
    }

    /**
     * @param ResponseInterface $passportPageResponse
     * @param string $login
     * @return ResponseInterface
     * @throws Exception
     * @throws HttpClientException
     */
    private function submitLoginByPassportPageResponse(ResponseInterface $passportPageResponse, string $login): ResponseInterface
    {
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

        $response =  $this->sendPostUrlEncodedRequest($uri, $postData);

        $this->validateResponse($response);

        return $response;
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

        $response =  $this->sendPostUrlEncodedRequest($uri, $postData);

        $this->validateResponse($response);

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

        if ($data['status'] !=='ok') {
            throw new Exception("Bad status ({$data['status']})for Password page. Body: " . $body);
        }
    }

    /**
     * @param string $uri
     * @param array $postData
     * @return ResponseInterface
     * @throws HttpClientException
     */
    private function sendPostUrlEncodedRequest(string $uri, array $postData = [])
    {
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        $body = http_build_query($postData);

        $stream = $this->streamFactory->createStream($body);

        return $this->sendPostRequest($uri, $stream, $headers);
    }

    /**
     * @param $uri
     * @param $body
     * @param array $headers
     * @return ResponseInterface
     * @throws HttpClientException
     */
    private function sendPostRequest(string $uri, StreamInterface $body = null, $headers = [])
    {
        $request = $this->createPostRequest($uri, $headers, $body);

        return $this->sendRequest($request);
    }


    /**
     * @return ResponseInterface
     * @throws Exception
     * @throws HttpClientException
     */
    private function getPassportPage()
    {
        $response = $this->sendGetRequest('https://passport.yandex.ru/auth/welcome?retpath=https%3A%2F%2Ffleet.taxi.yandex.ru');
        $this->validateResponse($response);

        return $response;
    }


    /**
     * @return ResponseInterface
     * @throws Exception
     * @throws HttpClientException
     */
    private function getDashboardPage()
    {
        $response = $this->sendGetRequest('https://fleet.taxi.yandex.ru/');
        $this->validateResponse($response);

        return $response;
    }

    /**
     * @param ResponseInterface $response
     * @throws Exception
     */
    private function validateResponse(ResponseInterface $response)
    {
        if (($responseStatusCode = $response->getStatusCode()) !== 200) {
            throw new Exception('Invalid response status code: ' . $responseStatusCode);
        }
    }

    /**
     * @param $url
     * @return ResponseInterface
     * @throws HttpClientException
     */
    private function sendGetRequest($url) :ResponseInterface
    {
        $request = $this->createGetRequest($url);

        return $this->sendRequest($request);
    }

    /**
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws HttpClientException
     */
    private function sendRequest(RequestInterface $request) :ResponseInterface
    {
        return $this->httpPluginClient->sendRequest($request);
    }

    private function createPostRequest($uri, $headers = [], StreamInterface $body = null) :RequestInterface
    {
        $request = $this->createRequest(HttpMethodInterface::POST, $uri);

        if ($headers) {
            $this->addHeaders($request, $headers);
        }

        if ($body) {
            $request = $request->withBody($body);
        }

        return $request;
    }

    private function addHeaders(RequestInterface $request, array $headers)
    {
        foreach ($headers as $key => $value) {
            $request->withHeader($key, $value);
        }
    }

    private function createGetRequest($uri, $headers = [], $body = null, $protocolVersion = '1.1'): RequestInterface
    {
        return $this->createRequest(HttpMethodInterface::GET, $uri);
    }

    /**
     * @param $httpMethod
     * @param $uri
     * @return RequestInterface
     */
    private function createRequest($httpMethod, $uri) :RequestInterface
    {
        return $this->requestFactory->createRequest(
            $httpMethod,
            $uri
        );
    }


    public function logout()
    {
        // TODO: Implement logout() method.
    }

    public function addDriverWithNewCar($driverWithNewCar)
    {
        // TODO: Implement addDriverWithNewCar() method.
    }
}
