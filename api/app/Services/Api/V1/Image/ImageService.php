<?php

namespace App\Services\Api\V1\Image;

use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class ImageService
{
    public function uploadImage($file, $folder = 'uploads')
    {
        $upload = Cloudinary::uploadApi()->upload($file->getRealPath(), [
            'folder' => $folder,
            'transformation' => [
                'quality' => 'auto',
                'fetch_format' => 'auto'
            ]
        ]);

        return $upload['secure_url'] ?? null;
    }
}
