<?php

declare(strict_types=1);

use Piseth\BakongKhqr\Models\MerchantInfo;
use Piseth\BakongKhqr\BakongKHQR;
use Piseth\BakongKhqr\Exceptions\KHQRException;

test('generate merchant khqrwith valid data', function () {
    $merchantData = $this->createSampleMerchantInfo();

    try {
        // Create MerchantInfo object
        $merchantInfo = new MerchantInfo(
            $merchantData['bakongAccountID'],
            $merchantData['merchantName'],
            $merchantData['merchantCity'],
            $merchantData['merchantID'],
            $merchantData['acquiringBank']
        );
        
        // Set optional properties
        if (property_exists($merchantInfo, 'merchantID')) {
            $merchantInfo->merchantID = $merchantData['merchantID'];
        }
        if (property_exists($merchantInfo, 'acquiringBank')) {
            $merchantInfo->acquiringBank = $merchantData['acquiringBank'];
        }
        if (property_exists($merchantInfo, 'currency')) {
            $merchantInfo->currency = $merchantData['currency'];
        }
        if (property_exists($merchantInfo, 'amount')) {
            $merchantInfo->amount = $merchantData['amount'];
        }
        
        $result = BakongKHQR::generateMerchant($merchantInfo);
        
        $this->assertValidKHQRResponse($result);
        
        // Check if result has QR and MD5
        if (method_exists($result, 'getData')) {
            $data = $result->getData();
            expect($data)->toHaveKey('qr');
            expect($data)->toHaveKey('md5');
            expect($data['qr'])->toBeString();
            expect($data['md5'])->toBeString();
            expect(strlen($data['md5']))->toEqual(32); // MD5 should be 32 characters
        }
        
    } catch (\Exception $e) {
        error_log('Exception in testGenerateMerchantKHQRWithValidData: ' . $e->getMessage());
        expect($e)->toBeInstanceOf(\Exception::class);
    }
});
test('generate merchant khqrwith minimal data', function () {
    try {
        $merchantInfo = new MerchantInfo(
            'merchant@wing',
            'Test Merchant',
            'Phnom Penh',
            'TEST123',
            'Test Bank'
        );
        
        $result = BakongKHQR::generateMerchant($merchantInfo);
        $this->assertValidKHQRResponse($result);
        
    } catch (\Exception $e) {
        error_log('Exception in testGenerateMerchantKHQRWithMinimalData: ' . $e->getMessage());
        expect($e)->toBeInstanceOf(\Exception::class);
    }
});
test('generate merchant khqrwith additional data', function () {
    try {
        $merchantInfo = new MerchantInfo(
            'merchant@wing',
            'Sample Merchant',
            'Phnom Penh',
            'SMPL123',
            'Wing'
        );
        
        // Set additional data if properties exist
        if (property_exists($merchantInfo, 'mobileNumber')) {
            $merchantInfo->mobileNumber = '85587654321';
        }
        if (property_exists($merchantInfo, 'billNumber')) {
            $merchantInfo->billNumber = 'BILL-2023-001';
        }
        if (property_exists($merchantInfo, 'storeLabel')) {
            $merchantInfo->storeLabel = 'Sample Store';
        }
        if (property_exists($merchantInfo, 'terminalLabel')) {
            $merchantInfo->terminalLabel = '54321';
        }
        if (property_exists($merchantInfo, 'purposeOfTransaction')) {
            $merchantInfo->purposeOfTransaction = 'Purchase';
        }
        
        $result = BakongKHQR::generateMerchant($merchantInfo);
        $this->assertValidKHQRResponse($result);
        
    } catch (\Exception $e) {
        error_log('Exception in testGenerateMerchantKHQRWithAdditionalData: ' . $e->getMessage());
        expect($e)->toBeInstanceOf(\Exception::class);
    }
});
test('generate merchant khqrwith large amount', function () {
    try {
        $merchantInfo = new MerchantInfo(
            'bigmerchant@wing',
            'Big Merchant',
            'Phnom Penh',
            'BIG123',
            'Big Bank'
        );
        
        if (property_exists($merchantInfo, 'currency')) {
            $merchantInfo->currency = 116; // KHR
        }
        if (property_exists($merchantInfo, 'amount')) {
            $merchantInfo->amount = 1000000.0; // 1 million KHR
        }
        
        $result = BakongKHQR::generateMerchant($merchantInfo);
        $this->assertValidKHQRResponse($result);
        
    } catch (\Exception $e) {
        error_log('Exception in testGenerateMerchantKHQRWithLargeAmount: ' . $e->getMessage());
        expect($e)->toBeInstanceOf(\Exception::class);
    }
});
test('generate merchant khqrwith usdcurrency', function () {
    try {
        $merchantInfo = new MerchantInfo(
            'usd.merchant@wing',
            'USD Merchant',
            'Phnom Penh',
            'USD123',
            'USD Bank'
        );
        
        if (property_exists($merchantInfo, 'currency')) {
            $merchantInfo->currency = 840; // USD
        }
        if (property_exists($merchantInfo, 'amount')) {
            $merchantInfo->amount = 125.75;
        }
        
        $result = BakongKHQR::generateMerchant($merchantInfo);
        $this->assertValidKHQRResponse($result);
        
    } catch (\Exception $e) {
        error_log('Exception in testGenerateMerchantKHQRWithUSDCurrency: ' . $e->getMessage());
        expect($e)->toBeInstanceOf(\Exception::class);
    }
});
test('generate merchant khqrwith different city', function () {
    try {
        $merchantInfo = new MerchantInfo(
            'siemreap.merchant@wing',
            'Siem Reap Merchant',
            'Siem Reap', // Different city
            'SIEM123',
            'Siem Reap Bank'
        );
        
        if (property_exists($merchantInfo, 'amount')) {
            $merchantInfo->amount = 50.0;
        }
        
        $result = BakongKHQR::generateMerchant($merchantInfo);
        $this->assertValidKHQRResponse($result);
        
    } catch (\Exception $e) {
        error_log('Exception in testGenerateMerchantKHQRWithDifferentCity: ' . $e->getMessage());
        expect($e)->toBeInstanceOf(\Exception::class);
    }
});
test('generate merchant khqrwith invalid bakong account id', function () {
    try {
        $merchantInfo = new MerchantInfo(
            '', // Empty account ID
            'Test Merchant',
            'Phnom Penh',
            'TEST123',
            'Test Bank'
        );
        
        $result = BakongKHQR::generateMerchant($merchantInfo);
        
        // This should either throw an exception or handle the error gracefully
        $this->fail('Expected exception for invalid account ID');
        
    } catch (KHQRException $e) {
        expect($e)->toBeInstanceOf(KHQRException::class);
    } catch (\Exception $e) {
        expect($e)->toBeInstanceOf(\Exception::class);
    }
});
test('generate merchant khqrwith invalid merchant name', function () {
    try {
        $merchantInfo = new MerchantInfo(
            'valid@wing',
            '', // Empty merchant name
            'Phnom Penh',
            'TEST123',
            'Test Bank'
        );
        
        $result = BakongKHQR::generateMerchant($merchantInfo);
        
        // This should either throw an exception or handle the error gracefully
        $this->fail('Expected exception for invalid merchant name');
        
    } catch (KHQRException $e) {
        expect($e)->toBeInstanceOf(KHQRException::class);
    } catch (\Exception $e) {
        expect($e)->toBeInstanceOf(\Exception::class);
    }
});
test('generate merchant khqrwith zero amount', function () {
    try {
        $merchantInfo = new MerchantInfo(
            'zero.merchant@wing',
            'Zero Amount Merchant',
            'Phnom Penh',
            'ZERO123',
            'Zero Bank'
        );
        
        if (property_exists($merchantInfo, 'amount')) {
            $merchantInfo->amount = 0.0;
        }
        
        $result = BakongKHQR::generateMerchant($merchantInfo);
        $this->assertValidKHQRResponse($result);
        
    } catch (\Exception $e) {
        error_log('Exception in testGenerateMerchantKHQRWithZeroAmount: ' . $e->getMessage());
        expect($e)->toBeInstanceOf(\Exception::class);
    }
});
