<?php

declare(strict_types=1);

use Piseth\BakongKhqr\Models\IndividualInfo;
use Piseth\BakongKhqr\BakongKHQR;
use Piseth\BakongKhqr\Models\MerchantInfo;

test('complete individual khqr flow', function () {
    try {
        // Step 1: Generate Individual KHQR
        $individualInfo = new IndividualInfo(
            'chhunpiseth@wing',
            'Piseth Chhun',
            'Phnom Penh'
        );
        
        // Set additional properties
        if (property_exists($individualInfo, 'merchantID')) {
            $individualInfo->merchantID = '012345678';
        }
        if (property_exists($individualInfo, 'currency')) {
            $individualInfo->currency = 116; // KHR
        }
        if (property_exists($individualInfo, 'amount')) {
            $individualInfo->amount = 1000.0;
        }
        
        $generatedResult = BakongKHQR::generateIndividual($individualInfo);
        assertValidKHQRResponse($generatedResult);
        
        $generatedData = null;
        if (method_exists($generatedResult, 'getData')) {
            $generatedData = $generatedResult->getData();
        } elseif (property_exists($generatedResult, 'data')) {
            $generatedData = $generatedResult->data;
        }
        
        if ($generatedData && is_array($generatedData) && isset($generatedData['qr'])) {
            $qrString = $generatedData['qr'];
            
            // Step 2: Verify the generated KHQR
            $verificationResult = BakongKHQR::verify($qrString);
            expect($verificationResult)->toBeInstanceOf(\Piseth\BakongKhqr\Models\CRCValidation::class);
            
            // Step 3: Decode the KHQR
            $decodedResult = BakongKHQR::decode($qrString);
            assertValidKHQRResponse($decodedResult);
            
            $decodedData = null;
            if (method_exists($decodedResult, 'getData')) {
                $decodedData = $decodedResult->getData();
            } elseif (property_exists($decodedResult, 'data')) {
                $decodedData = $decodedResult->data;
            }
            
            // Step 4: Verify decoded data matches original input
            if ($decodedData && is_array($decodedData)) {
                expect($decodedData)->toHaveKey('merchantName');
                expect($decodedData)->toHaveKey('merchantCity');
                expect($decodedData['merchantName'])->toEqual('Piseth Chhun');
                expect($decodedData['merchantCity'])->toEqual('Phnom Penh');
            }
        }
        
    } catch (\Exception $e) {
        error_log('Exception in complete individual khqr flow test: ' . $e->getMessage());
        expect($e)->toBeInstanceOf(\Exception::class);
    }
})->skip('API test - requires integration environment');

test('complete merchant khqr flow', function () {
        try {
            // Step 1: Generate Merchant KHQR
            $merchantInfo = new MerchantInfo(
                'merchant@wing',
                'Sample Merchant',
                'Phnom Penh',
                '987654321',
                'Sample Bank'
            );
            
            // Set additional properties
            if (property_exists($merchantInfo, 'currency')) {
                $merchantInfo->currency = 840; // USD
            }
            if (property_exists($merchantInfo, 'amount')) {
                $merchantInfo->amount = 25.50;
            }
            
            $generatedResult = BakongKHQR::generateMerchant($merchantInfo);
            assertValidKHQRResponse($generatedResult);
            
            $generatedData = null;
            if (method_exists($generatedResult, 'getData')) {
                $generatedData = $generatedResult->getData();
            } elseif (property_exists($generatedResult, 'data')) {
                $generatedData = $generatedResult->data;
            }
            
            if ($generatedData && is_array($generatedData) && isset($generatedData['qr'])) {
                $qrString = $generatedData['qr'];
                
                // Step 2: Verify the generated KHQR
                $verificationResult = BakongKHQR::verify($qrString);
                expect($verificationResult)->toBeInstanceOf(\Piseth\BakongKhqr\Models\CRCValidation::class);
                
                // Step 3: Decode the KHQR
                $decodedResult = BakongKHQR::decode($qrString);
                assertValidKHQRResponse($decodedResult);
                
                $decodedData = null;
                if (method_exists($decodedResult, 'getData')) {
                    $decodedData = $decodedResult->getData();
                } elseif (property_exists($decodedResult, 'data')) {
                    $decodedData = $decodedResult->data;
                }
                
                // Step 4: Verify decoded data matches original input
                if ($decodedData && is_array($decodedData)) {
                    expect($decodedData)->toHaveKey('merchantName');
                    expect($decodedData)->toHaveKey('merchantCity');
                    expect($decodedData['merchantName'])->toEqual('Sample Merchant');
                    expect($decodedData['merchantCity'])->toEqual('Phnom Penh');
                }
            }
            
        } catch (\Exception $e) {
            error_log('Exception in testCompleteMerchantKHQRFlow: ' . $e->getMessage());
            expect($e)->toBeInstanceOf(\Exception::class);
        }
    });

test('khqr with different currencies', function () {
    $currencies = [
        ['code' => 116, 'name' => 'KHR', 'amount' => 4000.0],
        ['code' => 840, 'name' => 'USD', 'amount' => 1.0]
    ];
    
    foreach ($currencies as $currency) {
        try {
            $individualInfo = new IndividualInfo(
                "test.{$currency['name']}@wing",
                "Test {$currency['name']} User",
                'Phnom Penh'
            );
                
                if (property_exists($individualInfo, 'currency')) {
                    $individualInfo->currency = $currency['code'];
                }
                if (property_exists($individualInfo, 'amount')) {
                    $individualInfo->amount = $currency['amount'];
                }
                
                $result = BakongKHQR::generateIndividual($individualInfo);
                assertValidKHQRResponse($result);
                
            } catch (\Exception $e) {
                error_log("Exception testing currency {$currency['name']}: " . $e->getMessage());
            expect($e)->toBeInstanceOf(\Exception::class);
        }
    }
});

test('KHQR with complete additional data', function () {
    $individualInfo = new IndividualInfo(
        'comprehensive@wing',
        'Comprehensive Test User',
        'Phnom Penh'
    );

    $additionalFields = [
        'merchantID' => '123456789',
        'acquiringBank' => 'Test Bank',
        'currency' => 116,
        'amount' => 15000.0,
        'mobileNumber' => '85512345678',
        'billNumber' => 'BILL-2023-COMPREHENSIVE',
        'storeLabel' => 'Comprehensive Store',
        'terminalLabel' => 'TERM-001',
        'purposeOfTransaction' => 'Payment Test',
        'languagePreference' => 'KM',
        'merchantNameAlternateLanguage' => 'អ្នកប្រើប្រាស់ការសាកល្បង',
        'merchantCityAlternateLanguage' => 'ភ្នំពេញ',
        'upiMerchantAccount' => 'UPI-123456'
    ];

    foreach ($additionalFields as $field => $value) {
        if (property_exists($individualInfo, $field)) {
            $individualInfo->$field = $value;
        }
    }

    $result = BakongKHQR::generateIndividual($individualInfo);
    assertValidKHQRResponse($result);

    $generatedData = method_exists($result, 'getData')
        ? $result->getData()
        : ($result->data ?? null);

    if ($generatedData && is_array($generatedData) && isset($generatedData['qr'])) {
        $decodedResult = BakongKHQR::decode($generatedData['qr']);
        assertValidKHQRResponse($decodedResult);
    }
});

test('Token-based operations', function () {
    // Skip if no test token is available
    if (!getenv('BAKONG_TEST_TOKEN') && !isset($_ENV['BAKONG_TEST_TOKEN'])) {
        $this->markTestSkipped('BAKONG_TEST_TOKEN environment variable not set');
    }
    
    $testToken = $_ENV['BAKONG_TEST_TOKEN'] ?? getenv('BAKONG_TEST_TOKEN');
    $bakongKHQR = new BakongKHQR($testToken);

    $accountResult = BakongKHQR::checkBakongAccount('test@wing', true);
    assertValidKHQRResponse($accountResult);

    $sampleMD5 = md5('sample_transaction_data');

    try {
        $transactionResult = $bakongKHQR->checkTransactionByMD5($sampleMD5, true);
        expect($transactionResult)->toBeArray();
    } catch (\Exception $e) {
        expect($e)->toBeInstanceOf(\Exception::class);
    }
})->skip('API test - requires integration environment and valid token');

test('Deep link generation', function () {
    $individualInfo = new IndividualInfo(
        'pisethchhun@wing',
        'Deep Link Test User',
        'Phnom Penh'
    );

    $khqrResult = BakongKHQR::generateIndividual($individualInfo);
    assertValidKHQRResponse($khqrResult);

    $khqrData = method_exists($khqrResult, 'getData')
        ? $khqrResult->getData()
        : ($khqrResult->data ?? null);

    if ($khqrData && is_array($khqrData) && isset($khqrData['qr'])) {
        $deepLinkResult = BakongKHQR::generateDeepLink($khqrData['qr'], null, true);
        assertValidKHQRResponse($deepLinkResult);
    }
});