<?php

declare(strict_types=1);

namespace Piseth\BakongKhqr\Models;

use Piseth\BakongKhqr\Helpers\EMV;
use Piseth\BakongKhqr\Models\TagLengthString;
use Piseth\BakongKhqr\Exceptions\KHQRException;
use Piseth\BakongKhqr\Utils\StringUtils;

class MerchantCity extends TagLengthString
{
    public function __construct(string $tag, ?string $value)
    {
        if (StringUtils::isEmpty($value)) {
            throw new KHQRException(KHQRException::MERCHANT_CITY_TAG_REQUIRED);
        }
        if (mb_strlen($value, 'UTF-8') > EMV::INVALID_LENGTH['MERCHANT_CITY']) {
            throw new KHQRException(KHQRException::MERCHANT_CITY_LENGTH_INVALID);
        }
        parent::__construct($tag, $value);
    }
}
