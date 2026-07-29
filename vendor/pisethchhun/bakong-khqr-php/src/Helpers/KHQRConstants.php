<?php

declare(strict_types=1);

namespace Piseth\BakongKhqr\Helpers;

use Piseth\BakongKhqr\Models\CRC;
use Piseth\BakongKhqr\Helpers\EMV;
use Piseth\BakongKhqr\Models\TimeStamp;
use Piseth\BakongKhqr\Models\CountryCode;
use Piseth\BakongKhqr\Models\MerchantCity;
use Piseth\BakongKhqr\Models\MerchantName;
use Piseth\BakongKhqr\Models\AdditionalData;
use Piseth\BakongKhqr\Models\TransactionAmount;
use Piseth\BakongKhqr\Models\TransactionCurrency;
use Piseth\BakongKhqr\Models\MerchantCategoryCode;
use Piseth\BakongKhqr\Models\GlobalUniqueIdentifier;
use Piseth\BakongKhqr\Models\PayloadFormatIndicator;
use Piseth\BakongKhqr\Models\PointOfInitiationMethod;
use Piseth\BakongKhqr\Models\UnionpayMerchantAccount;
use Piseth\BakongKhqr\Models\MerchantInformationLanguageTemplate;

/**
 * KHQRConstants class for handling KHQR-related constants
 */
class KHQRConstants
{
    public const MERCHANT_TYPE_MERCHANT = 'merchant';

    public const MERCHANT_TYPE_INDIVIDUAL = 'individual';

    public static $KHQR_TAG = [
        [
            'tag' => '00',
            'type' => 'payloadFormatIndicator',
            'required' => true,
            'instance' => PayloadFormatIndicator::class,
        ],
        [
            'tag' => '01',
            'type' => 'pointofInitiationMethod',
            'required' => false,
            'instance' => PointOfInitiationMethod::class,
        ],
        [
            'tag' => '15',
            'type' => 'unionPayMerchant',
            'required' => false,
            'instance' => UnionpayMerchantAccount::class,
        ],
        [
            'sub' => true,
            'tag' => '29',
            'type' => 'globalUnqiueIdentifier',
            'required' => true,
            'instance' => GlobalUniqueIdentifier::class,
        ],
        [
            'tag' => '52',
            'type' => 'merchantCategoryCode',
            'required' => true,
            'instance' => MerchantCategoryCode::class,
        ],
        [
            'tag' => '53',
            'type' => 'transactionCurrency',
            'required' => true,
            'instance' => TransactionCurrency::class,
        ],
        [
            'tag' => '54',
            'type' => 'transactionAmount',
            'required' => false,
            'instance' => TransactionAmount::class,
        ],
        [
            'tag' => '58',
            'type' => 'countryCode',
            'required' => true,
            'instance' => CountryCode::class,
        ],
        [
            'tag' => '59',
            'type' => 'merchantName',
            'required' => true,
            'instance' => MerchantName::class,
        ],
        [
            'tag' => '60',
            'type' => 'merchantCity',
            'required' => true,
            'instance' => MerchantCity::class,
        ],
        [
            'tag' => '62',
            'sub' => true,
            'type' => 'additionalData',
            'required' => false,
            'instance' => AdditionalData::class,
        ],
        [
            'tag' => '64',
            'sub' => true,
            'type' => 'merchantInformationLanguageTemplate',
            'required' => false,
            'instance' => MerchantInformationLanguageTemplate::class,
        ],
        [
            'tag' => '99',
            'type' => 'timestamp',
            'required' => false,
            'instance' => TimeStamp::class,
        ],
        [
            'tag' => '63',
            'type' => 'crc',
            'required' => true,
            'instance' => CRC::class,
        ],
    ];

    // KHQR_SUBTAG constants
    public static $KHQR_SUBTAG = [
        'input' => [
            [
                'tag' => '29',
                'data' => [
                    'bakongAccountID' => null,
                    'accountInformation' => null,
                ],
            ],
            [
                'tag' => '30',
                'data' => [
                    'bakongAccountID' => null,
                    'merchantID' => null,
                    'acquiringBank' => null,
                ],
            ],

            [
                'tag' => '62',
                'data' => [
                    'billNumber' => null,
                    'mobileNumber' => null,
                    'storeLabel' => null,
                    'terminalLabel' => null,
                    'purposeOfTransaction' => null,
                ],
            ],
            [
                'tag' => '64',
                'data' => [
                    'languagePreference' => null,
                    'merchantNameAlternateLanguage' => null,
                    'merchantCityAlternateLanguage' => null,
                ],
            ],
        ],
        'compare' => [
            [
                'tag' => '29',
                'subTag' => EMV::BAKONG_ACCOUNT_IDENTIFIER,
                'name' => 'bakongAccountID',
            ],
            [
                'tag' => '29',
                'subTag' => EMV::MERCHANT_ACCOUNT_INFORMATION_MERCHANT_ID,
                'name' => 'accountInformation',
            ],
            [
                'tag' => '29',
                'subTag' => EMV::MERCHANT_ACCOUNT_INFORMATION_ACQUIRING_BANK,
                'name' => 'acquiringBank',
            ],
            [
                'tag' => '62',
                'subTag' => EMV::BILLNUMBER_TAG,
                'name' => 'billNumber',
            ],
            [
                'tag' => '62',
                'subTag' => EMV::ADDITIONAL_DATA_FIELD_MOBILE_NUMBER,
                'name' => 'mobileNumber',
            ],
            [
                'tag' => '62',
                'subTag' => EMV::STORELABEL_TAG,
                'name' => 'storeLabel',
            ],
            [
                'tag' => '62',
                'subTag' => EMV::PURPOSE_OF_TRANSACTION,
                'name' => 'purposeOfTransaction',
            ],
            [
                'tag' => '62',
                'subTag' => EMV::TERMINAL_TAG,
                'name' => 'terminalLabel',
            ],
            [
                'tag' => '64',
                'subTag' => EMV::LANGUAGE_PREFERENCE,
                'name' => 'languagePreference',
            ],
            [
                'tag' => '64',
                'subTag' => EMV::MERCHANT_NAME_ALTERNATE_LANGUAGE,
                'name' => 'merchantNameAlternateLanguage',
            ],
            [
                'tag' => '64',
                'subTag' => EMV::MERCHANT_CITY_ALTERNATE_LANGUAGE,
                'name' => 'merchantCityAlternateLanguage',
            ],
        ],
    ];

    // CURRENCY constants
    public static $CURRENCY = [
        'USD' => '840',
        'KHR' => '116',
        'THB' => '764',
        'LAK' => '418',
        'VND' => '704',
        'MYR' => '458',
        'MMK' => '104',
        'BND' => '096',
        'PHP' => '608',
        'SGD' => '702',
        'IDR' => '360',
        'INR' => '356',
        'CNY' => '156',
        'AUD' => '036',
        'CZK' => '203',
        'EUR' => '978',
    ];

    // COUNTRY constants
    public static $COUNTRY = [
        'KH' => 'KH',
        'TH' => 'TH',
        'LA' => 'LA',
        'VN' => 'VN',
        'MY' => 'MY',
        'MM' => 'MM',
        'BN' => 'BN',
        'PH' => 'PH',
        'SG' => 'SG',
        'ID' => 'ID',
        'IN' => 'IN',
        'CN' => 'CN',
        'AU' => 'AU',
        'CZ' => 'CZ',
        'FR' => 'FR',
    ];

    // TAG constants
    public static $TAG = [
        'MERCHANT' => '30',
        'INDIVIDUAL' => '29',
        'TAG_02' => '02',
        'TAG_03' => '03',
        'TAG_04' => '04',
        'TAG_05' => '05',
        'TAG_06' => '06',
        'TAG_07' => '07',
        'TAG_08' => '08',
        'TAG_09' => '09',
        'TAG_10' => '10',
        'TAG_11' => '11',
        'TAG_12' => '12',
        'TAG_13' => '13',
        'TAG_14' => '14',
        'TAG_15' => '15',
        'TAG_16' => '16',
        'TAG_17' => '17',
        'TAG_18' => '18',
        'TAG_19' => '19',
        'TAG_20' => '20',
        'TAG_21' => '21',
        'TAG_22' => '22',
        'TAG_23' => '23',
        'TAG_24' => '24',
        'TAG_25' => '25',
        'TAG_26' => '26',
        'TAG_27' => '27',
        'TAG_28' => '28',
        'TAG_29' => '29',
        'TAG_30' => '30',
        'TAG_31' => '31',
        'TAG_32' => '32',
        'TAG_33' => '33',
        'TAG_34' => '34',
        'TAG_35' => '35',
        'TAG_36' => '36',
        'TAG_37' => '37',
        'TAG_38' => '38',
        'TAG_39' => '39',
        'TAG_40' => '40',
        'TAG_41' => '41',
        'TAG_42' => '42',
        'TAG_43' => '43',
        'TAG_44' => '44',
        'TAG_45' => '45',
        'TAG_46' => '46',
        'TAG_47' => '47',
        'TAG_48' => '48',
        'TAG_49' => '49',
        'TAG_50' => '50',
        'TAG_51' => '51',
    ];
}
