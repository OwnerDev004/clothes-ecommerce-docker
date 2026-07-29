<?php

declare(strict_types=1);

namespace Piseth\BakongKhqr\Helpers;

/**
 * ErrorCode class for handling error codes
 */
class ErrorCode
{
    public const BAKONG_ACCOUNT_ID_REQUIRED = [
        'code' => 1,
        'message' => 'Bakong Account ID cannot be null or empty',
    ];
    public const MERCHANT_NAME_REQUIRED = [
        'code' => 2,
        'message' => 'Merchant name cannot be null or empty',
    ];
    public const BAKONG_ACCOUNT_ID_INVALID = [
        'code' => 3,
        'message' => 'Bakong Account ID is invalid',
    ];
    public const TRANSACTION_AMOUNT_INVALID = [
        'code' => 4,
        'message' => 'Amount is invalid',
    ];
    public const MERCHANT_TYPE_REQUIRED = [
        'code' => 5,
        'message' => 'Merchant type cannot be null or empty',
    ];
    public const BAKONG_ACCOUNT_ID_LENGTH_INVALID = [
        'code' => 6,
        'message' => 'Bakong Account ID Length is Invalid',
    ];
    public const MERCHANT_NAME_LENGTH_INVALID = [
        'code' => 7,
        'message' => 'Merchant Name Length is invalid',
    ];
    public const KHQR_INVALID = [
        'code' => 8,
        'message' => 'KHQR provided is invalid',
    ];
    public const CURRENCY_TYPE_REQUIRED = [
        'code' => 9,
        'message' => 'Currency type cannot be null or empty',
    ];
    public const BILL_NUMBER_LENGTH_INVALID = [
        'code' => 10,
        'message' => 'Bill Name Length is invalid',
    ];
    public const STORE_LABEL_LENGTH_INVALID = [
        'code' => 11,
        'message' => 'Store Label Length is invalid',
    ];
    public const TERMINAL_LABEL_LENGTH_INVALID = [
        'code' => 12,
        'message' => 'Terminal Label Length is invalid',
    ];
    public const CONNECTION_TIMEOUT = [
        'code' => 13,
        'message' => 'Cannot reach Bakong Open API service. Please check internet connection',
    ];
    public const INVALID_DEEP_LINK_SOURCE_INFO = [
        'code' => 14,
        'message' => 'Source Info for Deep Link is invalid',
    ];
    public const INTERNAL_SREVER = [
        'code' => 15,
        'message' => 'Internal server error',
    ];
    public const PAYLOAD_FORMAT_INDICATOR_LENGTH_INVALID = [
        'code' => 16,
        'message' => 'Payload Format indicator Length is invalid',
    ];
    public const POINT_INITIATION_LENGTH_INVALID = [
        'code' => 17,
        'message' => 'Point of initiation Length is invalid',
    ];
    public const MERCHANT_CODE_LENGTH_INVALID = [
        'code' => 18,
        'message' => 'Merchant code Length is invalid',
    ];
    public const TRANSACTION_CURRENCY_LENGTH_INVALID = [
        'code' => 19,
        'message' => 'Transaction currency Length is invalid',
    ];
    public const COUNTRY_CODE_LENGTH_INVALID = [
        'code' => 20,
        'message' => 'Country code Length is invalid',
    ];
    public const MERCHANT_CITY_LENGTH_INVALID = [
        'code' => 21,
        'message' => 'Merchant city Length is invalid',
    ];
    public const CRC_LENGTH_INVALID = [
        'code' => 22,
        'message' => 'CRC Length is invalid',
    ];
    public const PAYLOAD_FORMAT_INDICATOR_TAG_REQUIRED = [
        'code' => 23,
        'message' => 'Payload format indicator tag required',
    ];
    public const CRC_TAG_REQUIRED = [
        'code' => 24,
        'message' => 'CRC tag required',
    ];
    public const MERCHANT_CATEGORY_TAG_REQUIRED = [
        'code' => 25,
        'message' => 'Merchant category tag required',
    ];
    public const COUNTRY_CODE_TAG_REQUIRED = [
        'code' => 26,
        'message' => 'Country Code cannot be null or empty',
    ];
    public const MERCHANT_CITY_TAG_REQUIRED = [
        'code' => 27,
        'message' => 'Merchant City cannot be null or empty',
    ];
    public const UNSUPPORTED_CURRENCY = [
        'code' => 28,
        'message' => 'Unsupported currency',
    ];
    public const INVALID_DEEP_LINK_URL = [
        'code' => 29,
        'message' => 'Deep Link URL is not valid',
    ];
    public const MERCHANT_ID_REQUIRED = [
        'code' => 30,
        'message' => 'Merchant ID cannot be null or empty',
    ];
    public const ACQUIRING_BANK_REQUIRED = [
        'code' => 31,
        'message' => 'Acquiring Bank cannot be null or empty',
    ];
    public const MERCHANT_ID_LENGTH_INVALID = [
        'code' => 32,
        'message' => 'Merchant ID Length is invalid',
    ];
    public const ACQUIRING_BANK_LENGTH_INVALID = [
        'code' => 33,
        'message' => 'Acquiring Bank Length is invalid',
    ];
    public const MOBILE_NUMBER_LENGTH_INVALID = [
        'code' => 34,
        'message' => 'Mobile Number Length is invalid',
    ];
    public const ACCOUNT_INFORMATION_LENGTH_INVALID = [
        'code' => 35,
        'message' => 'Account Information Length is invalid',
    ];
    public const TAG_NOT_IN_ORDER = [
        'code' => 36,
        'message' => 'Tag is not in order',
    ];
    public const LANGUAGE_PREFERENCE_REQUIRED = [
        'code' => 37,
        'message' => 'Language Preference cannot be null or empty',
    ];
    public const LANGUAGE_PREFERENCE_LENGTH_INVALID = [
        'code' => 38,
        'message' => 'Language Preference Length is invalid',
    ];
    public const MERCHANT_NAME_ALTERNATE_LANGUAGE_REQUIRED = [
        'code' => 39,
        'message' => 'Merchant Name Alternate Language cannot be null or empty',
    ];
    public const MERCHANT_NAME_ALTERNATE_LANGUAGE_LENGTH_INVALID = [
        'code' => 40,
        'message' => 'Merchant Name Alternate Language Length is invalid',
    ];
    public const MERCHANT_CITY_ALTERNATE_LANGUAGE_LENGTH_INVALID = [
        'code' => 41,
        'message' => 'Merchant City Alternate Language Length is invalid',
    ];
    public const PURPOSE_OF_TRANSACTION_LENGTH_INVALID = [
        'code' => 42,
        'message' => 'Purpose of Transaction Length is invalid',
    ];
    public const UPI_ACCOUNT_INFORMATION_LENGTH_INVALID = [
        'code' => 43,
        'message' => 'UPI Account Information Length is invalid',
    ];
}
