<?php

namespace App\Contracts;

use App\DTOs\ImageUploadResult;
use Illuminate\Http\UploadedFile;

interface ImageStorageInterface
{
    public function upload(UploadedFile $file, string $folder, array $options = []): ImageUploadResult;

    public function delete(?string $publicId): bool;

    public function extractPublicIdFromUrl(?string $url): ?string;
}
