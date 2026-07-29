<?php

declare(strict_types=1);

namespace Piseth\BakongKhqr\APIs;

use Piseth\BakongKhqr\Configs\Constants;
use Piseth\BakongKhqr\Utils\PostUtils;
use Piseth\BakongKhqr\Utils\StringUtils;

class Token
{
    /**
     * @return array<string, mixed>
     */
    public static function renewToken(string $email, bool $isTest = false): array
    {
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email address');
        }

        $url = $isTest ? Constants::SIT_RENEW_TOKEN_URL : Constants::RENEW_TOKEN_URL;

        return PostUtils::post_data_to_url($url, ['email' => $email]);
    }

    public static function isExpiredToken(string $token): bool
    {
        if (StringUtils::isEmpty($token)) {
            return true;
        }

        try {
            $exp = StringUtils::getExpirationDateFromJwtPayload($token);
            if ($exp == null) {
                return true;
            }

            return time() > $exp;
        } catch (\Exception $e) {
            var_dump('An exception occurred while validating expiration date from token: '.$token, $e);

            return true;
        }
    }
}
