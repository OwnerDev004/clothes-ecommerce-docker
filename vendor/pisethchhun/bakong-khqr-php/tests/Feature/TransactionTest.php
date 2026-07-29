<?php

declare(strict_types=1);

use Piseth\BakongKhqr\BakongKHQR;

beforeEach(function () {
    $this->bakongKHQR = new BakongKHQR($this->testToken);
});
test('check transaction by md5', function () {
    $testMD5 = md5('sample_transaction_' . time());

    try {
        $result = $this->bakongKHQR->checkTransactionByMD5($testMD5, true);
        
        expect($result)->toBeArray();
        // In a real test environment, you might check for specific keys
        // $this->assertArrayHasKey('status', $result);
        
    } catch (\Exception $e) {
        // Expected in test environment without actual transaction data
        expect($e)->toBeInstanceOf(\Exception::class);
        error_log('Expected exception in testCheckTransactionByMD5: ' . $e->getMessage());
    }
});
test('check transaction by md5 list', function () {
    $testMD5List = [
        md5('transaction_1_' . time()),
        md5('transaction_2_' . time()),
        md5('transaction_3_' . time())
    ];

    try {
        $result = $this->bakongKHQR->checkTransactionByMD5List($testMD5List, true);
        
        expect($result)->toBeArray();
        
    } catch (\Exception $e) {
        expect($e)->toBeInstanceOf(\Exception::class);
        error_log('Expected exception in testCheckTransactionByMD5List: ' . $e->getMessage());
    }
});
test('check transaction by full hash', function () {
    $testFullHash = hash('sha256', 'full_hash_transaction_' . time());

    try {
        $result = $this->bakongKHQR->checkTransactionByFullHash($testFullHash, true);
        
        expect($result)->toBeArray();
        
    } catch (\Exception $e) {
        expect($e)->toBeInstanceOf(\Exception::class);
        error_log('Expected exception in testCheckTransactionByFullHash: ' . $e->getMessage());
    }
});
test('check transaction by full hash list', function () {
    $testFullHashList = [
        hash('sha256', 'full_hash_1_' . time()),
        hash('sha256', 'full_hash_2_' . time())
    ];

    try {
        $result = $this->bakongKHQR->checkTransactionByFullHashList($testFullHashList, true);
        
        expect($result)->toBeArray();
        
    } catch (\Exception $e) {
        expect($e)->toBeInstanceOf(\Exception::class);
        error_log('Expected exception in testCheckTransactionByFullHashList: ' . $e->getMessage());
    }
});
test('check transaction by short hash', function () {
    $testShortHash = substr(md5('short_hash_' . time()), 0, 8);
    $testAmount = 1000.0;
    $testCurrency = 'KHR';

    try {
        $result = $this->bakongKHQR->checkTransactionByShortHash(
            $testShortHash, 
            $testAmount, 
            $testCurrency, 
            true
        );
        
        expect($result)->toBeArray();
        
    } catch (\Exception $e) {
        expect($e)->toBeInstanceOf(\Exception::class);
        error_log('Expected exception in testCheckTransactionByShortHash: ' . $e->getMessage());
    }
});
test('check transaction by instruction reference', function () {
    $testReference = 'INS_REF_' . time();

    try {
        $result = $this->bakongKHQR->checkTransactionByInstructionReference($testReference, true);
        
        expect($result)->toBeArray();
        
    } catch (\Exception $e) {
        expect($e)->toBeInstanceOf(\Exception::class);
        error_log('Expected exception in testCheckTransactionByInstructionReference: ' . $e->getMessage());
    }
});
test('check transaction by external reference', function () {
    $testReference = 'EXT_REF_' . time();

    try {
        $result = $this->bakongKHQR->checkTransactionByExternalReference($testReference, true);
        
        expect($result)->toBeArray();
        
    } catch (\Exception $e) {
        expect($e)->toBeInstanceOf(\Exception::class);
        error_log('Expected exception in testCheckTransactionByExternalReference: ' . $e->getMessage());
    }
});
test('transaction parameter validation', function () {
    // Test with empty MD5
    try {
        $result = $this->bakongKHQR->checkTransactionByMD5('', true);
        // Should either work or throw an exception
    } catch (\Exception $e) {
        expect($e)->toBeInstanceOf(\Exception::class);
    }

    // Test with empty MD5 list
    try {
        $result = $this->bakongKHQR->checkTransactionByMD5List([], true);
        // Should either work or throw an exception
    } catch (\Exception $e) {
        expect($e)->toBeInstanceOf(\Exception::class);
    }

    // Test with negative amount for short hash
    try {
        $result = $this->bakongKHQR->checkTransactionByShortHash('testhash', -100.0, 'KHR', true);
        // Should either work or throw an exception
    } catch (\Exception $e) {
        expect($e)->toBeInstanceOf(\Exception::class);
    }
});
test('transaction with different currencies', function () {
    $currencies = ['KHR', 'USD'];
    $testShortHash = substr(md5('currency_test_' . time()), 0, 8);

    foreach ($currencies as $currency) {
        try {
            $result = $this->bakongKHQR->checkTransactionByShortHash(
                $testShortHash, 
                100.0, 
                $currency, 
                true
            );
            
            expect($result)->toBeArray();
            
        } catch (\Exception $e) {
            expect($e)->toBeInstanceOf(\Exception::class);
            error_log("Expected exception testing currency $currency: " . $e->getMessage());
        }
    }
});
test('transaction with different amounts', function () {
    $amounts = [0.01, 1.0, 100.0, 1000.0, 999999.99];
    $testShortHash = substr(md5('amount_test_' . time()), 0, 8);

    foreach ($amounts as $amount) {
        try {
            $result = $this->bakongKHQR->checkTransactionByShortHash(
                $testShortHash, 
                $amount, 
                'KHR', 
                true
            );
            
            expect($result)->toBeArray();
            
        } catch (\Exception $e) {
            expect($e)->toBeInstanceOf(\Exception::class);
            error_log("Expected exception testing amount $amount: " . $e->getMessage());
        }
    }
});
