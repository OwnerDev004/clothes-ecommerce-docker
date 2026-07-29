<?php

declare(strict_types=1);

namespace Piseth\BakongKhqr\Models;

use Piseth\BakongKhqr\Helpers\EMV;
use Piseth\BakongKhqr\Utils\StringUtils;
use Piseth\BakongKhqr\Exceptions\KHQRException;

class CountryCode extends TagLengthString
{
    public function __construct(string $tag, ?string $value)
    {
        if (StringUtils::isEmpty($value)) {
            throw new KHQRException(KHQRException::COUNTRY_CODE_TAG_REQUIRED);
        }
        if (strlen($value) > EMV::INVALID_LENGTH['COUNTRY_CODE']) {
            throw new KHQRException(KHQRException::COUNTRY_CODE_LENGTH_INVALID);
        }
        parent::__construct($tag, $value);
    }
}
