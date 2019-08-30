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

class Client implements ClientInterface
{
    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var RequestFactoryInterface
     */
    private $requestFactory;

    /**
     * @var StreamFactory
     */
    private $streamFactory;
    //private $uriFactory;

    /**
     *
     * @param HttpClient $httpClient
     * @param RequestFactoryInterface $requestFactory
     * @param StreamFactory $streamFactory
     */
    public function __construct(
        HttpClient $httpClient,
        RequestFactoryInterface $requestFactory
//        StreamFactory $streamFactory
        //UriFactory $uriFactory
    ) {
        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory;
//        $this->streamFactory = $streamFactory;
        //$this->uriFactory = $uriFactory;
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
        $response = $this->openPassportPage();

        list($csrfToken, $processUuid, $retpath) = $this->getVarsFromPassportPageResponse($response);
        $this->submitLogin($login, $csrfToken, $processUuid, $retpath);
        //$this->submitPassword();

        //$request = $this->requestFactory->createRequest(HttpMethodInterface::POST, 'http://httplug.io', [], );
    }

    private function getVarsFromPassportPageResponse(ResponseInterface $response)
    {
        $bodyStream = $response->getBody();
        $body = $bodyStream->getContents();

        return $this->getVarsFromPassportPage($body);
    }

    private function getVarsFromPassportPage(string $body)
    {
        $csrfToken = null;
        $processUuid = null;

        return [
            $csrfToken,
            $processUuid,
        ];
    }


    private function submitLogin(string $login, string $csrfToken, string $processUuid, string $retpath)
    {
        $uri = 'https://passport.yandex.ru/registration-validations/auth/multi_step/start';
        $postData = [
            'csrf_token' => $csrfToken,
            'login' => $login,
            'process_uuid' => $processUuid,
            'retpath' => $retpath,
        ];

        return $this->sendPostUrlEncodedRequest($uri, $postData);
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
        $body = null;

        return $this->sendPostRequest($uri, $stream, $headers);
    }

    /**
     * @param $uri
     * @param $body
     * @param array $headers
     * @return ResponseInterface
     * @throws HttpClientException
     */
    private function sendPostRequest(string $uri, $body = null, $headers = [])
    {
        $request = $this->createPostRequest($uri, $headers, $body);

        return $this->sendRequest($request);
    }


    /**
     * @return ResponseInterface
     * @throws Exception
     * @throws HttpClientException
     */
    private function openPassportPage()
    {
        $response = $this->sendGetRequest('https://passport.yandex.ru/auth/welcome?retpath=https%3A%2F%2Ffleet.taxi.yandex.ru');
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
        return $this->httpClient->sendRequest($request);
    }

    private function createPostRequest($uri, $headers = [], $body = null) :RequestInterface
    {
        $request = $this->createRequest(HttpMethodInterface::POST, $uri);

        if ($headers) {
            $this->addHeaders($request, $headers);
        }

        if ($body) {
            $request->withBody($body);
        }
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
