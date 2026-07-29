<?php

declare(strict_types=1);

namespace Piseth\BakongKhqr\Models;

use Piseth\BakongKhqr\Utils\StringUtils;
use Piseth\BakongKhqr\Models\TagLengthString;
use Piseth\BakongKhqr\Exceptions\KHQRException;

class PointOfInitiationMethod extends TagLengthString
{
    public function __construct($tag, $value)
    {
        if (StringUtils::isEmpty($value) || strlen((string)$value) > 2) {
            throw new KHQRException(KHQRException::POINT_INITIATION_LENGTH_INVALID);
        }

        parent::__construct($tag, (string)$value);
    }
}