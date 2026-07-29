<?php

declare(strict_types=1);

namespace Piseth\BakongKhqr\Models;

use Piseth\BakongKhqr\Helpers\EMV;
use Piseth\BakongKhqr\Exceptions\KHQRException;
use Piseth\BakongKhqr\Utils\StringUtils;

class MerchantCategoryCode extends TagLengthString
{
    public function __construct(string $tag, ?string $value)
    {
        if (StringUtils::isEmpty($value)) {
            throw new KHQRException(KHQRException::MERCHANT_CATEGORY_TAG_REQUIRED);
        }
        if (strlen($value) > EMV::INVALID_LENGTH['MERCHANT_CATEGORY_CODE']) {
            throw new KHQRException(KHQRException::MERCHANT_CODE_LENGTH_INVALID);
        }
        parent::__construct($tag, $value);
    }
}
