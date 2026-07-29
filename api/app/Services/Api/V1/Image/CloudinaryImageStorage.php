<?php

namespace App\Services\Api\V1\Image;

use App\Contracts\ImageStorageInterface;
use App\DTOs\ImageUploadResult;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\UploadedFile;

class CloudinaryImageStorage implements ImageStorageInterface
{
    public function upload(UploadedFile $file, string $folder, array $options = []): ImageUploadResult
    {
        $upload = Cloudinary::uploadApi()->upload(
            $file->getRealPath(),
            array_merge([
                'folder' => $folder,
            ], $options)
        );

        return new ImageUploadResult(
            $upload['secure_url'] ?? null,
            $upload['public_id'] ?? null
        );
    }

    public function delete(?string $publicId): bool
    {
        if (!$publicId) {
            return false;
        }

        try {
            $result = Cloudinary::uploadApi()->destroy($publicId, [
                'resource_type' => 'image',
                'type' => 'upload',
            ]);

            return in_array($result['result'] ?? null, ['ok', 'not found'], true);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function extractPublicIdFromUrl(?string $url): ?string
    {
        if (empty($url)) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (!is_string($path) || strpos($path, '/upload/') === false) {
            return null;
        }

        $afterUpload = substr($path, strpos($path, '/upload/') + 8);
        $parts = array_values(array_filter(explode('/', ltrim($afterUpload, '/'))));
        if (empty($parts)) {
            return null;
        }

        foreach ($parts as $index => $part) {
            if (preg_match('/^v\d+$/', $part)) {
                $parts = array_slice($parts, $index + 1);
                break;
            }
        }

        if (empty($parts)) {
            return null;
        }

        $publicIdWithExtension = implode('/', $parts);
        $publicId = preg_replace('/\.[^.\/]+$/', '', $publicIdWithExtension);

        return is_string($publicId) && $publicId !== '' ? urldecode($publicId) : null;
    }
}
