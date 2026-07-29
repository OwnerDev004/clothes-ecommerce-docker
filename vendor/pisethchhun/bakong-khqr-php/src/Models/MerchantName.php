<?php

declare(strict_types=1);

namespace Piseth\BakongKhqr\Models;

use Piseth\BakongKhqr\Helpers\EMV;
use Piseth\BakongKhqr\Utils\StringUtils;
use Piseth\BakongKhqr\Models\TagLengthString;
use Piseth\BakongKhqr\Exceptions\KHQRException;

class MerchantName extends TagLengthString
{
    public function __construct(string $tag, ?string $value)
    {
        if (StringUtils::isEmpty($value)) {
            throw new KHQRException(KHQRException::MERCHANT_NAME_REQUIRED);
        }
        if (mb_strlen($value, 'UTF-8') > EMV::INVALID_LENGTH['MERCHANT_NAME']) {
            throw new KHQRException(KHQRException::MERCHANT_NAME_LENGTH_INVALID);
        }
        parent::__construct($tag, $value);
    }
}
