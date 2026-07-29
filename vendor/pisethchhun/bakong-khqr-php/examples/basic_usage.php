<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Piseth\BakongKhqr\Models\IndividualInfo;
use Piseth\BakongKhqr\BakongKHQR;
use Piseth\BakongKhqr\Models\MerchantInfo;
use Piseth\BakongKhqr\Models\SourceInfo;

/**
 * Example usage of the Bakong KHQR PHP package
 */

// Your Bakong API token (replace with your actual token)
$token = 'YOUR_BAKONG_API_TOKEN_HERE';

echo "=== Bakong KHQR PHP Example ===\n\n";

try {
    // Initialize BakongKHQR with token
    $bakongKHQR = new BakongKHQR($token);
    echo "✓ BakongKHQR initialized successfully\n\n";

    // Example 1: Generate Individual KHQR
    echo "1. Generating Individual KHQR...\n";
    
    $individualInfo = new IndividualInfo(
        'chhunpiseth@wing',    // Account ID
        'Piseth Chhun',        // Merchant Name
        'Phnom Penh'           // Merchant City
    );
    
    // Set optional properties
    if (property_exists($individualInfo, 'merchantID')) {
        $individualInfo->merchantID = '012345678';
    }
    if (property_exists($individualInfo, 'acquiringBank')) {
        $individualInfo->acquiringBank = 'Dev Bank';
    }
    if (property_exists($individualInfo, 'currency')) {
        $individualInfo->currency = 116; // KHR
    }
    if (property_exists($individualInfo, 'amount')) {
        $individualInfo->amount = 1000.0;
    }
    
    // Set additional data
    if (property_exists($individualInfo, 'mobileNumber')) {
        $individualInfo->mobileNumber = '85512345678';
    }
    if (property_exists($individualInfo, 'billNumber')) {
        $individualInfo->billNumber = 'INV-2022-12-25';
    }
    if (property_exists($individualInfo, 'storeLabel')) {
        $individualInfo->storeLabel = 'Ishin Shop';
    }
    if (property_exists($individualInfo, 'terminalLabel')) {
        $individualInfo->terminalLabel = '012345';
    }
    if (property_exists($individualInfo, 'purposeOfTransaction')) {
        $individualInfo->purposeOfTransaction = 'Payment';
    }
    
    // Set language data
    if (property_exists($individualInfo, 'languagePreference')) {
        $individualInfo->languagePreference = 'ZH';
    }
    if (property_exists($individualInfo, 'merchantNameAlternateLanguage')) {
        $individualInfo->merchantNameAlternateLanguage = '文山';
    }
    if (property_exists($individualInfo, 'merchantCityAlternateLanguage')) {
        $individualInfo->merchantCityAlternateLanguage = '金边';
    }
    
    $individualResult = BakongKHQR::generateIndividual($individualInfo);
    
    if (method_exists($individualResult, 'getData')) {
        $individualData = $individualResult->getData();
        echo "✓ Individual KHQR generated successfully!\n";
        echo "QR Code: " . substr($individualData['qr'], 0, 50) . "...\n";
        echo "MD5: " . $individualData['md5'] . "\n\n";
        
        // Verify the generated KHQR
        echo "2. Verifying Individual KHQR...\n";
        $verification = BakongKHQR::verify($individualData['qr']);
        if (property_exists($verification, 'isValid') && $verification->isValid) {
            echo "✓ KHQR is valid!\n\n";
        } else {
            echo "✗ KHQR validation failed\n\n";
        }
        
        // Decode the KHQR
        echo "3. Decoding Individual KHQR...\n";
        $decoded = BakongKHQR::decode($individualData['qr']);
        if (method_exists($decoded, 'getData')) {
            $decodedData = $decoded->getData();
            echo "✓ KHQR decoded successfully!\n";
            echo "Merchant Name: " . ($decodedData['merchantName'] ?? 'N/A') . "\n";
            echo "Merchant City: " . ($decodedData['merchantCity'] ?? 'N/A') . "\n";
            echo "Amount: " . ($decodedData['amount'] ?? 'N/A') . "\n\n";
        }
    }

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "This is expected in a test environment without proper API setup.\n\n";
}

try {
    // Example 2: Generate Merchant KHQR
    echo "4. Generating Merchant KHQR...\n";
    
    $merchantInfo = new MerchantInfo(
        'merchant@wing',       // Account ID
        'Sample Merchant',     // Merchant Name
        'Phnom Penh',          // Merchant City
        '987654321',           // Merchant ID
        'Sample Bank'          // Acquiring Bank
    );
    
    // Set optional properties
    if (property_exists($merchantInfo, 'currency')) {
        $merchantInfo->currency = 840; // USD
    }
    if (property_exists($merchantInfo, 'amount')) {
        $merchantInfo->amount = 25.50;
    }
    
    $merchantResult = BakongKHQR::generateMerchant($merchantInfo);
    
    if (method_exists($merchantResult, 'getData')) {
        $merchantData = $merchantResult->getData();
        echo "✓ Merchant KHQR generated successfully!\n";
        echo "QR Code: " . substr($merchantData['qr'], 0, 50) . "...\n";
        echo "MD5: " . $merchantData['md5'] . "\n\n";
    }

} catch (Exception $e) {
    echo "✗ Error generating merchant KHQR: " . $e->getMessage() . "\n\n";
}

try {
    // Example 3: Check Bakong Account
    echo "5. Checking Bakong Account...\n";
    
    $accountCheck = BakongKHQR::checkBakongAccount('test@wing', true);
    echo "✓ Account check completed\n\n";

} catch (Exception $e) {
    echo "✗ Error checking account: " . $e->getMessage() . "\n\n";
}

try {
    // Example 4: Transaction Checking
    echo "6. Checking Transaction by MD5...\n";
    
    $sampleMD5 = md5('sample_transaction_' . time());
    $transactionResult = $bakongKHQR->checkTransactionByMD5($sampleMD5, true);
    echo "✓ Transaction check completed\n\n";

} catch (Exception $e) {
    echo "✗ Error checking transaction: " . $e->getMessage() . "\n";
    echo "This is expected without actual transaction data.\n\n";
}

try {
    // Example 5: Token Renewal
    echo "7. Renewing Token...\n";
    
    $tokenRenewal = BakongKHQR::renewToken('test@example.com', true);
    echo "✓ Token renewal completed\n\n";

} catch (Exception $e) {
    echo "✗ Error renewing token: " . $e->getMessage() . "\n\n";
}

echo "=== Example completed ===\n";
echo "Note: Some operations may fail in a test environment without proper API setup.\n";
echo "This is normal and demonstrates the error handling capabilities of the package.\n";
