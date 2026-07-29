<?php

declare(strict_types=1);

namespace Piseth\BakongKhqr\APIs;

use Piseth\BakongKhqr\Helpers\EMV;
use Piseth\BakongKhqr\Utils\PostUtils;
use Piseth\BakongKhqr\Exceptions\KHQRException;

class Account
{
    /**
     * @param  string  $url  Bakong API endopint for checking account status
     * @param  string  $accountID  Bakong account ID
     * @return array<string, bool> info on account existence
     */
    public static function checkBakongAccountExistence(string $url, string $accountID): array
    {
        // Check account ID length
        if (strlen($accountID) > EMV::INVALID_LENGTH['BAKONG_ACCOUNT']) {
            throw new KHQRException(KHQRException::BAKONG_ACCOUNT_ID_LENGTH_INVALID);
        }

        if (substr_count($accountID, '@') != 1) {
            throw new KHQRException(KHQRException::BAKONG_ACCOUNT_ID_INVALID);
        }

        $respData = PostUtils::post_data_to_url($url, ['accountId' => $accountID]);

        // Handle response codes
        if (isset($respData['errorCode'])) {
            $error = $respData['errorCode'];
            if ($error == 11) {
                return ['bakongAccountExists' => false];
            }
            if ($error == 12) {
                throw new KHQRException(KHQRException::BAKONG_ACCOUNT_ID_INVALID);
            }
        }

        if (isset($respData['responseCode']) && $respData['responseCode'] == 0) {
            return ['bakongAccountExists' => true];
        }

        return ['bakongAccountExists' => false];
    }
}
