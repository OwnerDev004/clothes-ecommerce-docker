<?php

declare(strict_types=1);

namespace Piseth\BakongKhqr\Models;

use Piseth\BakongKhqr\Helpers\EMV;
use Piseth\BakongKhqr\Models\TagLengthString;
use Piseth\BakongKhqr\Exceptions\KHQRException;

class TransactionAmount extends TagLengthString
{
    public function __construct(string $tag, string $value)
    {
        if (
            strlen($value) > EMV::INVALID_LENGTH['AMOUNT'] ||
            strpos($value, '-') !== false ||
            $value === '' ||
            $value == null
        ) {
            throw new KHQRException(KHQRException::TRANSACTION_AMOUNT_INVALID);
        }

        parent::__construct($tag, $value);
    }
}
