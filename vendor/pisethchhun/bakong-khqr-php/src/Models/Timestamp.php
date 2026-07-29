<?php

declare(strict_types=1);

namespace Piseth\BakongKhqr\Models;

use Piseth\BakongKhqr\Models\TagLengthString;

class Timestamp extends TagLengthString
{
    public function __construct(string $tag)
    {
        $millisecondTimestamp = floor(microtime(true) * 1000);
        $timestamp = new TimestampMillisecond('00', (string) $millisecondTimestamp);
        $value = (string) $timestamp;
        parent::__construct($tag, $value);
    }
}

class TimestampMillisecond extends TagLengthString
{
    public function __construct(string $tag, string $value)
    {
        parent::__construct($tag, $value);
    }
}
