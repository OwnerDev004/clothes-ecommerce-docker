<?php

declare(strict_types=1);

namespace Piseth\BakongKhqr\Helpers;

/**
 * EMV Helper class for handling EMV-related operations
 */
class EMV
{
    public const PAYLOAD_FORMAT_INDICATOR = '00';
    public const POINT_OF_INITIATION_METHOD = '01';
    public const STATIC_QR = '11';
    public const DYNAMIC_QR = '12';
    public const MERCHANT_ACCOUNT_INFORMATION_INDIVIDUAL = '29';
    public const MERCHANT_ACCOUNT_INFORMATION_MERCHANT = '30';
    public const BAKONG_ACCOUNT_IDENTIFIER = '00';
    public const MERCHANT_ACCOUNT_INFORMATION_MERCHANT_ID = '01';
    public const INDIVIDUAL_ACCOUNT_INFORMATION = '01';
    public const MERCHANT_ACCOUNT_INFORMATION_ACQUIRING_BANK = '02';
    public const MERCHANT_CATEGORY_CODE = '52';
    public const TRANSACTION_CURRENCY = '53';
    public const TRANSACTION_AMOUNT = '54';
    public const COUNTRY_CODE = '58';
    public const MERCHANT_NAME = '59';
    public const MERCHANT_CITY = '60';
    public const CRC = '63';
    public const CRC_LENGTH = '04';
    public const ADDITIONAL_DATA_TAG = '62';
    public const BILLNUMBER_TAG = '01';
    public const ADDITIONAL_DATA_FIELD_MOBILE_NUMBER = '02';
    public const STORELABEL_TAG = '03';
    public const TERMINAL_TAG = '07';
    public const PURPOSE_OF_TRANSACTION = '08';
    public const TIMESTAMP_TAG = '99';
    public const MERCHANT_INFORMATION_LANGUAGE_TEMPLATE = '64';
    public const LANGUAGE_PREFERENCE = '00';
    public const MERCHANT_NAME_ALTERNATE_LANGUAGE = '01';
    public const MERCHANT_CITY_ALTERNATE_LANGUAGE = '02';
    public const UNIONPAY_MERCHANT_ACCOUNT = '15';

    // Default values for KHQR
    public const DEFAULT_PAYLOAD_FORMAT_INDICATOR = '01';
    public const DEFAULT_MERCHANT_CATEGORY_CODE = '5999';
    public const DEFAULT_TRANSACTION_AMOUNT = '0';
    public const DEFAULT_COUNTRY_CODE = 'KH';
    public const DEFAULT_MERCHANT_CITY = 'Phnom Penh';

    // Invalid length constants
    public const INVALID_LENGTH = [
        'KHQR' => 12,
        'MERCHANT_NAME' => 25,
        'BAKONG_ACCOUNT' => 32,
        'AMOUNT' => 13,
        'COUNTRY_CODE' => 3,
        'MERCHANT_CATEGORY_CODE' => 4,
        'MERCHANT_CITY' => 15,
        'TIMESTAMP' => 13,
        'TRANSACTION_AMOUNT' => 14,
        'TRANSACTION_CURRENCY' => 3,
        'BILL_NUMBER' => 25,
        'STORE_LABEL' => 25,
        'TERMINAL_LABEL' => 25,
        'PURPOSE_OF_TRANSACTION' => 25,
        'MERCHANT_ID' => 32,
        'ACQUIRING_BANK' => 32,
        'MOBILE_NUMBER' => 25,
        'ACCOUNT_INFORMATION' => 32,
        'MERCHANT_INFORMATION_LANGUAGE_TEMPLATE' => 99,
        'UPI_MERCHANT' => 99,
        'LANGUAGE_PREFERENCE' => 2,
        'MERCHANT_NAME_ALTERNATE_LANGUAGE' => 25,
        'MERCHANT_CITY_ALTERNATE_LANGUAGE' => 15,
    ];
}

