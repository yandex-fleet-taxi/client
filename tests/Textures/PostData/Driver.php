<?php

return [
    'accounts' =>
        [
            'balance_limit' => '5',
        ],
    'driver_profile' =>
        [
            'driver_license' =>
                [
                    'country' => 'rus',
                    'number' => 'OA111534', //overridden
                    'expiration_date' => '2021-01-13',
                    'issue_date' => '2011-01-13',
//                        'birth_date' => null,
//                        'birth_date' => '1985-02-01',
                ],
            'first_name' => 'Валерий',
            'last_name' => 'Иващенко',
            'middle_name' => 'Игоревич',
            'phones' => //overridden
                [
                    '+375333012955',
//                            '+7 (753) 330-12-95',
                ],
            'work_rule_id' => 'bea83f1024f94dfd980a97a26705f070', //overridden
            'balance_deny_onlycard' => false,
            'providers' =>
                [
                    0 => 'yandex',
                ],
            'hire_date' => '2019-09-01', //overridden
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
