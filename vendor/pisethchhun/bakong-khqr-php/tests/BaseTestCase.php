<?php

declare(strict_types=1);

namespace Piseth\BakongKhqr\Tests;

use PHPUnit\Framework\TestCase;

abstract class BaseTestCase extends TestCase
{
    protected string $testToken;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->testToken = $_ENV['BAKONG_TEST_TOKEN'];
    }

    /**
     * Create sample individual info for testing
     */
    protected function createSampleIndividualInfo(): array
    {
        return [
            'bakongAccountID' => 'chhunpiseth@wing',
            'merchantName' => 'Piseth Chhun',
            'merchantID' => '012345678',
            'acquiringBank' => 'Dev Bank',
            'merchantCity' => 'Phnom Penh',
            'currency' => 116, // KHR
            'amount' => 1000.0,
            'countryCode' => 'KH',
            'additionalData' => [
                'mobileNumber' => '85512345678',
                'billNumber' => 'INV-2022-12-25',
                'storeLabel' => 'Ishin Shop',
                'terminalLabel' => '012345',
                'purposeOfTransaction' => 'Payment'
            ],
            'languageData' => [
                'languagePreference' => 'ZH',
                'merchantNameAlternateLanguage' => '文山',
                'merchantCityAlternateLanguage' => '金边'
            ],
            'upiMerchantAccount' => ''
        ];
    }

    /**
     * Create sample merchant info for testing
     */
    protected function createSampleMerchantInfo(): array
    {
        return [
            'bakongAccountID' => 'merchant@wing',
            'merchantName' => 'Sample Merchant',
            'merchantID' => '987654321',
            'acquiringBank' => 'Sample Bank',
            'merchantCity' => 'Phnom Penh',
            'currency' => 116, // KHR
            'amount' => 2500.0,
            'countryCode' => 'KH',
            'additionalData' => [
                'mobileNumber' => '85587654321',
                'billNumber' => 'BILL-2023-001',
                'storeLabel' => 'Sample Store',
                'terminalLabel' => '54321',
                'purposeOfTransaction' => 'Purchase'
            ]
        ];
    }

    /**
     * Get sample KHQR string for testing
     */
    protected function getSampleKHQRString(): string
    {
        // This should be a valid KHQR string generated from the system
        return '00020101021229370010kh.gov.nbc.bakong011chhunpiseth@wing520400005303116540410005802KH5911Piseth Chhun6011Phnom Penh622901052085512345678022INV-2022-12-2503910Ishin Shop05012345063041234';
    }

    /**
     * Assert that a KHQR response is valid
     */
    protected function assertValidKHQRResponse($response): void
    {
        $this->assertNotNull($response);
        $this->assertIsObject($response);
        $this->assertTrue(property_exists($response, 'data') || method_exists($response, 'getData'));
    }
}
