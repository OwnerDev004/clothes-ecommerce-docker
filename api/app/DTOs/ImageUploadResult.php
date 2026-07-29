<?php

namespace App\DTOs;

final class ImageUploadResult
{
    public function __construct(
        public readonly ?string $url,
        public readonly ?string $publicId,
    ) {
    }
}
