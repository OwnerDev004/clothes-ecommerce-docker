<?php

declare(strict_types=1);

namespace Piseth\BakongKhqr\Models;

use Piseth\BakongKhqr\Utils\StringUtils;
use Piseth\BakongKhqr\Models\TagLengthString;
use Piseth\BakongKhqr\Exceptions\KHQRException;

/**
 * PayloadFormatIndicator class for handling payload format indicators
 */
class PayloadFormatIndicator extends TagLengthString
{
    public function __construct(string $tag, ?string $value)
    {
        if (StringUtils::isEmpty($value)) {
            throw new KHQRException(KHQRException::PAYLOAD_FORMAT_INDICATOR_TAG_REQUIRED);
        }
        if (strlen($value) > 2) {
            throw new KHQRException(KHQRException::PAYLOAD_FORMAT_INDICATOR_LENGTH_INVALID);
        }
        parent::__construct($tag, $value);
    }
}

