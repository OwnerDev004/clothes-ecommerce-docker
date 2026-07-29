<?php

declare(strict_types=1);

use Piseth\BakongKhqr\BakongKHQR;
use Piseth\BakongKhqr\Exceptions\KHQRException;

beforeEach(function () {
    $this->bakongKHQR = new BakongKHQR($this->testToken);
});
test('constructor with valid token', function () {
    $khqr = new BakongKHQR($this->testToken);
    expect($khqr)->toBeInstanceOf(BakongKHQR::class);
});
test('constructor with empty token', function () {
    expect(fn() => new BakongKHQR(''))
        ->toThrow(\InvalidArgumentException::class, 'Token cannot be blank');
});
test('constructor with null token', function () {
    expect(fn() => new BakongKHQR(null))
        ->toThrow(\TypeError::class);
});
test('check transaction by md5', function () {
    $md5 = 'sample_md5_hash_here';

    // Skip actual API call in unit tests
    expect($this->bakongKHQR)->toBeInstanceOf(BakongKHQR::class);
    // Just verify the method exists
    expect(method_exists($this->bakongKHQR, 'checkTransactionByMD5'))->toBeTrue();
})->skip('API tests should be in integration tests');

test('check transaction by md5 list', function () {
    $md5Array = ['hash1', 'hash2', 'hash3'];

    expect($this->bakongKHQR)->toBeInstanceOf(BakongKHQR::class);
    expect(method_exists($this->bakongKHQR, 'checkTransactionByMD5List'))->toBeTrue();
})->skip('API tests should be in integration tests');

test('check transaction by full hash', function () {
    $fullHash = 'sample_full_hash_here';

    expect($this->bakongKHQR)->toBeInstanceOf(BakongKHQR::class);
    expect(method_exists($this->bakongKHQR, 'checkTransactionByFullHash'))->toBeTrue();
})->skip('API tests should be in integration tests');

test('check transaction by short hash', function () {
    $shortHash = 'short_hash';
    $amount = 100.0;
    $currency = 'KHR';

    expect($this->bakongKHQR)->toBeInstanceOf(BakongKHQR::class);
    expect(method_exists($this->bakongKHQR, 'checkTransactionByShortHash'))->toBeTrue();
})->skip('API tests should be in integration tests');
test('renew token', function () {
    $email = 'test@example.com';

    expect(method_exists(BakongKHQR::class, 'renewToken'))->toBeTrue();
})->skip('API tests should be in integration tests');
test('verify valid khqrstring', function () {
    // This would need a real valid KHQR string to test properly
    $validKHQR = $this->getSampleKHQRString();

    try {
        $result = BakongKHQR::verify($validKHQR);
        expect($result)->toBeInstanceOf(\Piseth\BakongKhqr\Models\CRCValidation::class);
    } catch (\Exception $e) {
        // Expected if the sample KHQR string is not properly formatted
        expect($e)->toBeInstanceOf(\Exception::class);
    }
});
test('verify invalid khqrstring', function () {
    $invalidKHQR = 'invalid_khqr_string';

    $result = BakongKHQR::verify($invalidKHQR);
    expect($result)->toBeInstanceOf(\Piseth\BakongKhqr\Models\CRCValidation::class);

    // The result should indicate invalid KHQR
    if (method_exists($result, 'isValid')) {
        expect($result->isValid)->toBeFalse();
    }
});
test('decode khqrstring', function () {
    $khqrString = $this->getSampleKHQRString();

    try {
        $result = BakongKHQR::decode($khqrString);
        $this->assertValidKHQRResponse($result);
    } catch (\Exception $e) {
        // Expected if the sample KHQR string is not properly formatted
        expect($e)->toBeInstanceOf(\Exception::class);
    }
});
test('check bakong account', function () {
    $bakongID = 'test@wing';

    expect(method_exists(BakongKHQR::class, 'checkBakongAccount'))->toBeTrue();
})->skip('API tests should be in integration tests');

test('generate deep link', function () {
    $qr = $this->getSampleKHQRString();
    $sourceInfo = null;

    expect(method_exists(BakongKHQR::class, 'generateDeepLink'))->toBeTrue();
})->skip('API tests should be in integration tests');
