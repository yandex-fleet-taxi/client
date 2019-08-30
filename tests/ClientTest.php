<?php
declare(strict_types=1);

namespace Likemusic\YandexFleetTaxiClient\Tests;

use Likemusic\YandexFleetTaxiClient\Client;
use PHPUnit\Framework\TestCase;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\MessageFactoryDiscovery;
use Http\Discovery\Psr17FactoryDiscovery;

final class ClientTest extends TestCase
{
    /**
     * @doesNotPerformAssertions
     */
    public function testLogin()
    {
        $httpClient = HttpClientDiscovery::find();
        $requestFactory = Psr17FactoryDiscovery::findRequestFactory();
//        $streamFactory

        $client = new Client($httpClient, $requestFactory);

        $login = 'login';
        $password = 'password';
        $rememberMe = true;
        $client->login($login, $password, $rememberMe);
    }
}
