<?php

declare(strict_types=1);

namespace Piseth\BakongKhqr\Tests\Fixtures;

/**
 * Test data fixtures for KHQR testing
 */
class KHQRTestData
{
    /**
     * Sample valid KHQR strings for testing
     */
    public static array $validKHQRSamples = [
        'individual_basic' => '00020101021229370010kh.gov.nbc.bakong0115chhunpiseth@wing5204000053031165802KH5911Piseth Chhun6011Phnom Penh6304ABCD',
        'merchant_with_amount' => '00020101021230370010kh.gov.nbc.bakong0112merchant@wing520400005303116540410005802KH5915Sample Merchant6011Phnom Penh6304EFGH',
    ];

    /**
     * Sample invalid KHQR strings for testing
     */
    public static array $invalidKHQRSamples = [
        'too_short' => '000201',
        'invalid_format' => 'invalid_khqr_string',
        'missing_crc' => '00020101021229370010kh.gov.nbc.bakong0115test@wing',
        'wrong_crc' => '00020101021229370010kh.gov.nbc.bakong0115test@wing630499999',
    ];

    /**
     * Sample individual info data
     */
    public static function getIndividualInfoData(): array
    {
        return [
            'basic' => [
                'bakongAccountID' => 'test@wing',
                'merchantName' => 'Test User',
                'merchantCity' => 'Phnom Penh'
            ],
            'with_amount' => [
                'bakongAccountID' => 'amount@wing',
                'merchantName' => 'Amount Test User',
                'merchantCity' => 'Phnom Penh',
                'currency' => 116,
                'amount' => 1000.0
            ],
            'comprehensive' => [
                'bakongAccountID' => 'chhunpiseth@wing',
                'merchantName' => 'Piseth Chhun',
                'merchantCity' => 'Phnom Penh',
                'merchantID' => '012345678',
                'acquiringBank' => 'Dev Bank',
                'currency' => 116,
                'amount' => 1000.0,
                'mobileNumber' => '85512345678',
                'billNumber' => 'INV-2022-12-25',
                'storeLabel' => 'Ishin Shop',
                'terminalLabel' => '012345',
                'purposeOfTransaction' => 'Payment',
                'languagePreference' => 'ZH',
                'merchantNameAlternateLanguage' => '文山',
                'merchantCityAlternateLanguage' => '金边'
            ]
        ];
    }

    /**
     * Sample merchant info data
     */
    public static function getMerchantInfoData(): array
    {
        return [
            'basic' => [
                'bakongAccountID' => 'merchant@wing',
                'merchantName' => 'Test Merchant',
                'merchantCity' => 'Phnom Penh'
            ],
            'with_usd' => [
                'bakongAccountID' => 'usd.merchant@wing',
                'merchantName' => 'USD Merchant',
                'merchantCity' => 'Phnom Penh',
                'currency' => 840,
                'amount' => 25.50
            ],
            'comprehensive' => [
                'bakongAccountID' => 'comprehensive.merchant@wing',
                'merchantName' => 'Comprehensive Merchant',
                'merchantCity' => 'Siem Reap',
                'merchantID' => '987654321',
                'acquiringBank' => 'Merchant Bank',
                'currency' => 116,
                'amount' => 5000.0,
                'mobileNumber' => '85587654321',
                'billNumber' => 'BILL-2023-001',
                'storeLabel' => 'Main Store',
                'terminalLabel' => 'TERM-001',
                'purposeOfTransaction' => 'Purchase'
            ]
        ];
    }

    /**
     * Sample transaction data for testing
     */
    public static function getTransactionTestData(): array
    {
        return [
            'md5_hashes' => [
                '5d41402abc4b2a76b9719d911017c592', // "hello"
                '098f6bcd4621d373cade4e832627b4f6', // "test"
                '202cb962ac59075b964b07152d234b70'  // "123"
            ],
            'full_hashes' => [
                '2cf24dba4f21d4288094e08b83f30a47b17bb6a7cb5b66b67b7d3c24fd2d8a3bc',
                '9a0364b9e99bb480dd25e1f0284c8555f6f5999ab3deeef90f3bc3ee7a9b2c3d'
            ],
            'short_hashes' => [
                '5d41402a',
                '098f6bcd',
                '202cb962'
            ],
            'references' => [
                'instruction' => [
                    'INS_REF_001',
                    'INS_REF_002',
                    'INSTRUCTION_2023_001'
                ],
                'external' => [
                    'EXT_REF_001',
                    'EXT_REF_002',
                    'EXTERNAL_2023_001'
                ]
            ]
        ];
    }

    /**
     * Currency codes and test data
     */
    public static function getCurrencyTestData(): array
    {
        return [
            'KHR' => [
                'code' => 116,
                'symbol' => 'KHR',
                'amounts' => [100.0, 1000.0, 10000.0, 100000.0]
            ],
            'USD' => [
                'code' => 840,
                'symbol' => 'USD',
                'amounts' => [1.0, 10.0, 100.0, 1000.0]
            ]
        ];
    }

    /**
     * Generate test MD5 for current timestamp
     */
    public static function generateTestMD5(): string
    {
        return md5('test_' . time() . '_' . random_int(1000, 9999));
    }

    /**
     * Generate test full hash for current timestamp
     */
    public static function generateTestFullHash(): string
    {
        return hash('sha256', 'test_' . time() . '_' . random_int(1000, 9999));
    }

    /**
     * Generate test short hash
     */
    public static function generateTestShortHash(): string
    {
        return substr(self::generateTestMD5(), 0, 8);
    }

    /**
     * Generate test reference
     */
    public static function generateTestReference(string $prefix = 'TEST'): string
    {
        return $prefix . '_' . date('Y') . '_' . str_pad((string)random_int(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get expected decoded KHQR structure
     */
    public static function getExpectedDecodedStructure(): array
    {
        return [
            'merchantType' => 'string',
            'merchantName' => 'string',
            'merchantCity' => 'string',
            'payloadFormatIndicator' => 'string',
            'countryCode' => 'string'
        ];
    }
}
