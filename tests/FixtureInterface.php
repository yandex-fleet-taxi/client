<?php

namespace Likemusic\YandexFleetTaxiClient\Tests;


interface FixtureInterface
{
    const TEST_DRIVER_DATA = [
        'accounts' =>
            [
                'balance_limit' => '5',
            ],
        'driver_profile' =>
            [
                'driver_license' =>
                    [
                        'country' => 'rus',
                        'number' => '32132132',
                        'expiration_date' => '2019-09-20',
                        'issue_date' => '2019-09-01',
                        'birth_date' => '1985-02-01',
                    ],
                'first_name' => 'Валерий',
                'last_name' => 'Иващенко',
                'middle_name' => 'Игоревич',
                'phones' =>
                    [
                        '+77533301295',
//                            '+7 (753) 330-12-95',
                    ],
                'work_rule_id' => 'a6cb3fbe61a54ba28f8f8b5e35b286db',
                'balance_deny_onlycard' => false,
                'providers' =>
                    [
                        0 => 'yandex',
                    ],
//                    'hire_date' => '2019-09-01',
//                    'deaf' => NULL,
//                    'email' => NULL,
//                    'address' => NULL,
//                    'comment' => NULL,
//                    'check_message' => NULL,
//                    'car_id' => NULL,
//                    'fire_date' => NULL,
//                    'bank_accounts' => [],
//                    'emergency_person_contacts' => [],
//                    'identifications' => [],
//                    'primary_state_registration_number' => null,
//                    'tax_identification_number' => null,
            ],
    ];
}
