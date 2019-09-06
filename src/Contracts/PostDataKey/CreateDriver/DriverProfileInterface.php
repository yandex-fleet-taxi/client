<?php

namespace Likemusic\YandexFleetTaxiClient\Contracts\PostDataKey\CreateDriver;

interface DriverProfileInterface
{
    const ADDRESS = 'address';
    const CAR_ID = 'car_id';
    const CHECK_MESSAGE = 'check_message';
    const COMMENT = 'comment';
    const DEAF = 'deaf';
    const DRIVER_LICENSE = 'driver_license';
    const EMAIL = 'email';
    const FIRE_DATE = 'fire_date';
    const FIRST_NAME = 'first_name';
    const HIRE_DATE = 'hire_date';
    const LAST_NAME = 'last_name';
    const MIDDLE_NAME = 'middle_name';
    const PHONES = 'phones';
    const PROVIDERS = 'providers';
    const WORK_RULE_ID = 'work_rule_id';
    const WORK_STATUS = 'work_status';

    const BANK_ACCOUNTS = 'bank_accounts';
    const EMERGENCY_PERSON_CONTACTS = 'emergency_person_contacts';
    const IDENTIFICATIONS = 'identifications';
    const PRIMARY_STATE_REGISTRATION_NUMBER = 'primary_state_registration_number';
    const TAX_IDENTIFICATION_NUMBER = 'tax_identification_number';
}
