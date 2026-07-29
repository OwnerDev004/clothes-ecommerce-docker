<?php

declare(strict_types=1);

namespace Piseth\BakongKhqr\Models;

class TagLengthString
{
    public string $tag;

    public string $value;

    public string $length;

    public function __construct(string $tag, string $value)
    {
        $this->tag = $tag;
        $this->value = $value;

        $length = mb_strlen((string)$value, 'UTF-8');
        $this->length = $length < 10 ? '0' . $length : (string)$length;
    }

    public function __toString(): string
    {
        return $this->tag.$this->length.$this->value;
    }
}
