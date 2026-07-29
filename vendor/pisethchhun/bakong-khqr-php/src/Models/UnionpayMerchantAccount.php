<?php

declare(strict_types=1);

namespace Piseth\BakongKhqr\Models;

use Piseth\BakongKhqr\Helpers\EMV;
use Piseth\BakongKhqr\Models\TagLengthString;
use Piseth\BakongKhqr\Exceptions\KHQRException;

class UnionpayMerchantAccount extends TagLengthString
{
    public function __construct(string $tag, string $value)
    {
        if (mb_strlen($value, 'UTF-8') > EMV::INVALID_LENGTH['UPI_MERCHANT']) {
            throw new KHQRException(KHQRException::UPI_ACCOUNT_INFORMATION_LENGTH_INVALID);
        }
        parent::__construct($tag, $value);
    }
}