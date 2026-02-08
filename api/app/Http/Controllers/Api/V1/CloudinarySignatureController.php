<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Cloudinary\Api\ApiUtils;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class CloudinarySignatureController extends Controller
{
    public function avatar(Request $request): JsonResponse
    {
        $folder = 'clothes_ecommerce/customers/avatars';
        $timestamp = time();

        $cloudinary = $this->getCloudinaryCredentials();

        $paramsToSign = [
            'folder' => $folder,
            'timestamp' => $timestamp,
        ];

        $signature = ApiUtils::signParameters($paramsToSign, $cloudinary['secret']);

        return response()->json([
            'cloud_name' => $cloudinary['cloud'],
            'api_key' => $cloudinary['key'],
            'timestamp' => $timestamp,
            'signature' => $signature,
            'folder' => $folder,
        ]);
    }

    private function getCloudinaryCredentials(): array
    {
        $config = config('filesystems.disks.cloudinary', []);

        if (!empty($config['cloud']) && !empty($config['key']) && !empty($config['secret'])) {
            return [
                'cloud' => $config['cloud'],
                'key' => $config['key'],
                'secret' => $config['secret'],
            ];
        }

        $url = $config['url'] ?? env('CLOUDINARY_URL');
        if ($url) {
            $parts = parse_url($url);
            $cloud = $parts['host'] ?? null;
            $key = $parts['user'] ?? null;
            $secret = $parts['pass'] ?? null;

            if ($cloud && $key && $secret) {
                return [
                    'cloud' => $cloud,
                    'key' => $key,
                    'secret' => $secret,
                ];
            }
        }

        throw new RuntimeException('Cloudinary credentials are missing. Set CLOUDINARY_URL or CLOUDINARY_* env vars.');
    }
}
