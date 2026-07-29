<?php

declare(strict_types=1);

namespace Piseth\BakongKhqr\Models;

use Piseth\BakongKhqr\Helpers\KHQRConstants;
use Piseth\BakongKhqr\Models\TagLengthString;
use Piseth\BakongKhqr\Exceptions\KHQRException;

class TransactionCurrency extends TagLengthString
{
    public function __construct(string $tag, $value)
    {
        if ($value == null) {
            throw new KHQRException(KHQRException::CURRENCY_TYPE_REQUIRED);
        }

        $value = (string) $value;

        if (strlen($value) > 3) {
            throw new KHQRException(KHQRException::TRANSACTION_CURRENCY_LENGTH_INVALID);
        }
        if (! in_array((int) $value, KHQRConstants::$CURRENCY)) {
            throw new KHQRException(KHQRException::UNSUPPORTED_CURRENCY);
        }
        parent::__construct($tag, $value);
    }
}
