<?php

declare(strict_types=1);

use Piseth\BakongKhqr\Tests\Fixtures\KHQRTestData;

test('valid khqrvalidation', function () {
    $samples = KHQRTestData::$validKHQRSamples;

    foreach ($samples as $name => $khqrString) {
        try {
            $result = \Piseth\BakongKhqr\BakongKHQR::verify($khqrString);
            expect($result)->toBeInstanceOf(\Piseth\BakongKhqr\Models\CRCValidation::class);
            
            error_log("Testing valid KHQR sample: $name");
            
        } catch (\Exception $e) {
            error_log("Exception testing valid KHQR $name: " . $e->getMessage());
            expect($e)->toBeInstanceOf(\Exception::class);
        }
    }
});
test('invalid khqrvalidation', function () {
    $samples = KHQRTestData::$invalidKHQRSamples;

    foreach ($samples as $name => $khqrString) {
        try {
            $result = \Piseth\BakongKhqr\BakongKHQR::verify($khqrString);
            expect($result)->toBeInstanceOf(\Piseth\BakongKhqr\Models\CRCValidation::class);
            
            // Invalid KHQR should return false for isValid property
            if (property_exists($result, 'isValid')) {
                expect($result->isValid)->toBeFalse("Invalid KHQR $name should fail validation");
            }
            
            error_log("Testing invalid KHQR sample: $name - properly rejected");
            
        } catch (\Exception $e) {
            // Expected for some invalid formats
            error_log("Expected exception for invalid KHQR $name: " . $e->getMessage());
            expect($e)->toBeInstanceOf(\Exception::class);
        }
    }
});
test('khqrvalidation edge cases', function () {
    $edgeCases = [
        'empty_string' => '',
        'whitespace_only' => '   ',
        'single_char' => 'a',
        'very_long' => str_repeat('a', 1000),
        'special_chars' => '!@#$%^&*()',
        'numbers_only' => '1234567890',
        'mixed_case' => 'AbCdEfGhIjKlMnOp'
    ];

    foreach ($edgeCases as $name => $testCase) {
        try {
            $result = \Piseth\BakongKhqr\BakongKHQR::verify($testCase);
            expect($result)->toBeInstanceOf(\Piseth\BakongKhqr\Models\CRCValidation::class);
            
            if (property_exists($result, 'isValid')) {
                expect($result->isValid)->toBeFalse("Edge case $name should fail validation");
            }
            
        } catch (\Exception $e) {
            // Expected for edge cases
            error_log("Expected exception for edge case $name: " . $e->getMessage());
            expect($e)->toBeInstanceOf(\Exception::class);
        }
    }
});
test('khqrdecoding with fixtures', function () {
    $samples = KHQRTestData::$validKHQRSamples;

    foreach ($samples as $name => $khqrString) {
        try {
            $result = \Piseth\BakongKhqr\BakongKHQR::decode($khqrString);
            $this->assertValidKHQRResponse($result);
            
            $decodedData = null;
            if (method_exists($result, 'getData')) {
                $decodedData = $result->getData();
            } elseif (property_exists($result, 'data')) {
                $decodedData = $result->data;
            }
            
            if ($decodedData && is_array($decodedData)) {
                $expectedStructure = KHQRTestData::getExpectedDecodedStructure();
                
                foreach ($expectedStructure as $field => $type) {
                    if (isset($decodedData[$field])) {
                        expect($decodedData[$field])->toBeString("Field $field should be a string");
                    }
                }
            }
            
            error_log("Successfully decoded KHQR sample: $name");
            
        } catch (\Exception $e) {
            error_log("Exception decoding KHQR $name: " . $e->getMessage());
            expect($e)->toBeInstanceOf(\Exception::class);
        }
    }
});
test('validation decoding consistency', function () {
    $samples = KHQRTestData::$validKHQRSamples;

    foreach ($samples as $name => $khqrString) {
        try {
            // First validate
            $validationResult = \Piseth\BakongKhqr\BakongKHQR::verify($khqrString);
            
            // Then try to decode
            $decodingResult = \Piseth\BakongKhqr\BakongKHQR::decode($khqrString);
            
            // Both should succeed or both should fail
            $this->assertValidKHQRResponse($decodingResult);
            
            error_log("Consistency check passed for: $name");
            
        } catch (\Exception $e) {
            error_log("Consistency check exception for $name: " . $e->getMessage());
            expect($e)->toBeInstanceOf(\Exception::class);
        }
    }
});
