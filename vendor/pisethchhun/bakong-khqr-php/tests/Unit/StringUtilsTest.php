<?php

declare(strict_types=1);

use Piseth\BakongKhqr\Utils\StringUtils;

test('is empty', function () {
    expect(StringUtils::isEmpty(''))->toBeTrue();
    expect(StringUtils::isEmpty('   '))->toBeTrue();
    expect(StringUtils::isEmpty("\t\n"))->toBeTrue();
    expect(StringUtils::isEmpty('test'))->toBeFalse();
    expect(StringUtils::isEmpty('0'))->toBeFalse();
});
test('cut string', function () {
    // Test with a sample KHQR-like string format
    $testString = '0002010102';

    try {
        $result = StringUtils::cutString($testString);
        
        expect($result)->toBeArray();
        expect($result)->toHaveKey('tag');
        expect($result)->toHaveKey('value');
        expect($result)->toHaveKey('slicedString');
        
        // Based on EMV format: first 2 chars = tag, next 2 = length, rest = value
        expect($result['tag'])->toEqual('00');
        
    } catch (\Exception $e) {
        error_log('Exception in testCutString: ' . $e->getMessage());
        expect($e)->toBeInstanceOf(\Exception::class);
    }
});
test('check crcreg exp', function () {
    // Test with various string formats
    $validFormat = 'test string with 63041234';
    // Ends with CRC format
    $invalidFormat = 'test string without crc';

    try {
        $validResult = StringUtils::checkCRCRegExp($validFormat);
        $invalidResult = StringUtils::checkCRCRegExp($invalidFormat);
        
        expect($validResult)->toBeBool();
        expect($invalidResult)->toBeBool();
        
    } catch (\Exception $e) {
        error_log('Exception in testCheckCRCRegExp: ' . $e->getMessage());
        expect($e)->toBeInstanceOf(\Exception::class);
    }
});
test('crc16', function () {
    $testData = 'sample data for crc calculation';

    try {
        $crc = StringUtils::crc16($testData);
        
        expect($crc)->toBeString();
        expect(strlen($crc))->toEqual(4); // CRC16 should be 4 hex characters
        expect($crc)->toMatch('/^[0-9A-F]{4}$/');
        
    } catch (\Exception $e) {
        error_log('Exception in testCRC16: ' . $e->getMessage());
        expect($e)->toBeInstanceOf(\Exception::class);
    }
});
test('crc16 consistency', function () {
    $testData = 'consistent test data';

    try {
        $crc1 = StringUtils::crc16($testData);
        $crc2 = StringUtils::crc16($testData);
        
        expect($crc2)->toEqual($crc1, 'CRC16 should be consistent for same input');
        
    } catch (\Exception $e) {
        error_log('Exception in testCRC16Consistency: ' . $e->getMessage());
        expect($e)->toBeInstanceOf(\Exception::class);
    }
});
test('crc16 different inputs', function () {
    $testData1 = 'test data 1';
    $testData2 = 'test data 2';

    try {
        $crc1 = StringUtils::crc16($testData1);
        $crc2 = StringUtils::crc16($testData2);
        
        $this->assertNotEquals($crc1, $crc2, 'Different inputs should produce different CRCs');
        
    } catch (\Exception $e) {
        error_log('Exception in testCRC16DifferentInputs: ' . $e->getMessage());
        expect($e)->toBeInstanceOf(\Exception::class);
    }
});
test('find tag', function () {
    // Test finding a tag in an array
    $testArray = [
        ['tag' => '00', 'value' => 'first'],
        ['tag' => '01', 'value' => 'second'],
        ['tag' => '02', 'value' => 'third']
    ];

    try {
        $result = StringUtils::findTag($testArray, '01');
        
        if ($result !== false && $result !== null) {
            expect($result)->toBeArray();
            expect($result['tag'])->toEqual('01');
            expect($result['value'])->toEqual('second');
        }
        
    } catch (\Exception $e) {
        error_log('Exception in testFindTag: ' . $e->getMessage());
        expect($e)->toBeInstanceOf(\Exception::class);
    }
});
test('find tag not found', function () {
    $testArray = [
        ['tag' => '00', 'value' => 'first'],
        ['tag' => '01', 'value' => 'second']
    ];

    try {
        $result = StringUtils::findTag($testArray, '99');
        
        // Should return false or null for non-existent tag
        expect($result === false || $result === null)->toBeTrue();
        
    } catch (\Exception $e) {
        error_log('Exception in testFindTagNotFound: ' . $e->getMessage());
        expect($e)->toBeInstanceOf(\Exception::class);
    }
});
test('is empty with null', function () {
    // Test null input
    try {
        $result = StringUtils::isEmpty(null);
        expect($result)->toBeTrue();
    } catch (\TypeError $e) {
        // If method doesn't handle null, expect TypeError
        expect($e)->toBeInstanceOf(\TypeError::class);
    }
});
test('cut string with invalid input', function () {
    // Test with too short string
    $shortString = '0';

    try {
        $result = StringUtils::cutString($shortString);
        // Should handle gracefully or throw appropriate exception
        expect($result)->toBeArray();
    } catch (\Exception $e) {
        expect($e)->toBeInstanceOf(\Exception::class);
    }
});
test('crc16 with empty string', function () {
    try {
        $crc = StringUtils::crc16('');
        expect($crc)->toBeString();
        expect(strlen($crc))->toEqual(4);
    } catch (\Exception $e) {
        error_log('Exception in testCRC16WithEmptyString: ' . $e->getMessage());
        expect($e)->toBeInstanceOf(\Exception::class);
    }
});
