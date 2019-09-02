<?php

namespace Likemusic\YandexFleetTaxiClient\Contracts;

interface ClientInterface
{
    public function login(string $login, string $password);
//    public function logout();
    public function getDashboardPageData();
    public function changeLanguage(string $languageCode): array;
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
    ):array;

    public function createDriver(string $parkId, array $postData): string;
    public function getVehiclesCardData(string $parkId);
    public function getVehiclesCardModels(string $brandName);
    public function storeVehicles(array $postData);
    public function bindDriverWithCar(string $parkId, string $driverId, string $carId);
}
