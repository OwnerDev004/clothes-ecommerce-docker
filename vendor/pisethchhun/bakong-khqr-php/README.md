# Bakong KHQR PHP

A comprehensive PHP package for generating, parsing, and validating Bakong KHQR (Cambodian QR payment) codes. This package provides tools for developers to easily integrate with Cambodia's Bakong payment system.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/pisethchhun/bakong-khqr-php.svg?style=flat-square)](https://packagist.org/packages/pisethchhun/bakong-khqr-php)
[![Total Downloads](https://img.shields.io/packagist/dt/pisethchhun/bakong-khqr-php.svg?style=flat-square)](https://packagist.org/packages/pisethchhun/bakong-khqr-php)

## Features

- Generate Bakong KHQR codes for individuals and merchants
- Parse and validate existing KHQR codes
- Support for both KHR and USD currencies
- Deep link generation for mobile apps
- Token-based operations for account verification
- Comprehensive test suite using Pest
- PSR-4 autoloading and PSR-12 code style

## Requirements

- PHP 8.0 or higher

## Installation

You can install the package via Composer:

```bash
composer require pisethchhun/bakong-khqr-php
```

## Usage

### Generating Individual KHQR

```php
use Piseth\BakongKhqr\Models\IndividualInfo;
use Piseth\BakongKhqr\BakongKHQR;

// Create individual information
$individualInfo = new IndividualInfo(
    'username@wing',    // Account ID
    'Your Name',        // Merchant Name
    'Phnom Penh'        // Merchant City
);

// Set optional properties
$individualInfo->merchantID = '012345678';
$individualInfo->currency = 116; // KHR (840 for USD)
$individualInfo->amount = 1000.0;

// Generate the KHQR
$result = BakongKHQR::generateIndividual($individualInfo);

// Get the QR code string
$qrString = $result->getData()['qr'];

// Get the QR code image URL
$qrImageUrl = $result->getData()['qrImageUrl'];
```

### Generating Merchant KHQR

```php
use Piseth\BakongKhqr\Models\MerchantInfo;
use Piseth\BakongKhqr\BakongKHQR;

// Create merchant information
$merchantInfo = new MerchantInfo(
    'merchant@wing',      // Account ID
    'Merchant Name',      // Merchant Name
    'Phnom Penh',         // Merchant City
    '987654321',          // Merchant ID
    'Bank Name'           // Acquiring Bank
);

// Set optional properties
$merchantInfo->currency = 840; // USD (116 for KHR)
$merchantInfo->amount = 25.50;

// Generate the KHQR
$result = BakongKHQR::generateMerchant($merchantInfo);
```

### Verifying a KHQR Code

```php
use Piseth\BakongKhqr\BakongKHQR;

// Verify the KHQR code
$verificationResult = BakongKHQR::verify($qrString);

// Check if valid
if ($verificationResult->isValid()) {
    echo "KHQR is valid!";
}
```

### Decoding a KHQR Code

```php
use Piseth\BakongKhqr\BakongKHQR;

// Decode the KHQR code
$decodedResult = BakongKHQR::decode($qrString);

// Access decoded data
$decodedData = $decodedResult->getData();
echo "Merchant Name: " . $decodedData['merchantName'];
echo "Amount: " . ($decodedData['amount'] ?? 'Not specified');
```

### Using Additional Data Fields

```php
// Set all possible additional fields
$additionalFields = [
    'merchantID' => '123456789',
    'acquiringBank' => 'Test Bank',
    'currency' => 116,
    'amount' => 15000.0,
    'mobileNumber' => '85512345678',
    'billNumber' => 'BILL-2023-001',
    'storeLabel' => 'Main Store',
    'terminalLabel' => 'TERM-001',
    'purposeOfTransaction' => 'Payment for services',
    'languagePreference' => 'KM',
    'merchantNameAlternateLanguage' => 'អ្នកលក់',
    'merchantCityAlternateLanguage' => 'ភ្នំពេញ',
    'upiMerchantAccount' => 'UPI-123456'
];

// Apply additional fields to individual info
foreach ($additionalFields as $field => $value) {
    if (property_exists($individualInfo, $field)) {
        $individualInfo->$field = $value;
    }
}
```

### Token-Based Operations

```php
use Piseth\BakongKhqr\BakongKHQR;

// Initialize with token
$token = 'your-bakong-api-token';
$bakongKHQR = new BakongKHQR($token);

// Check if a Bakong account exists
$accountResult = $bakongKHQR->checkBakongAccount('test@wing');

// Check transaction status
$transactionMD5 = md5('transaction-reference');
$transactionResult = $bakongKHQR->checkTransactionByMD5($transactionMD5);
```

### Deep Link Generation

```php
use Piseth\BakongKhqr\BakongKHQR;

// Generate deep link from KHQR code
$deepLinkResult = BakongKHQR::generateDeepLink($qrString);
$deepLink = $deepLinkResult->getData()['deeplink'];
```

## Advanced Usage

For more advanced usage examples, please check the [examples](./examples) directory.

## Testing

The package uses Pest for testing. To run the tests:

```bash
composer test
```

To run specific test suites:

```bash
composer test:unit     # Run unit tests only
composer test:feature  # Run feature tests only
```

## Code Style

This package follows PSR-12 coding standards. You can check and fix code style issues with:

```bash
composer cs-check  # Check code style
composer cs-fix    # Fix code style issues
```

## Static Analysis

To run static analysis:

```bash
composer analyse
```

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Security

If you discover any security related issues, please email chhunpiseth.mail@gmail.com instead of using the issue tracker.

## Credits

- [Piseth Chhun](https://github.com/pisethx)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
