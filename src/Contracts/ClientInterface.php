<?php

namespace Likemusic\YandexFleetTaxiClient\Contracts;

interface ClientInterface
{
    public function login(string $login, string $password, bool $rememberMe = false);
    public function logout();

    public function addDriverWithNewCar($driverWithNewCar);

//    public function getCarBrands();
//    public function getCarBrandModels($carBrand);
}
