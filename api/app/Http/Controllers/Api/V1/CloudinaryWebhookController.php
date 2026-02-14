<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Cloudinary\Utils\SignatureVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CloudinaryWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $body = $request->getContent();
        $timestamp = $request->header('X-Cld-Timestamp');
        $signature = $request->header('X-Cld-Signature');

        if (!SignatureVerifier::verifyNotificationSignature($body, $timestamp, $signature)) {
            Log::warning('Cloudinary webhook signature invalid', [
                'timestamp' => $timestamp,
                'signature' => $signature,
            ]);

            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $payload = json_decode($body, true);
        Log::info('Cloudinary webhook received', [
            'notification_type' => $payload['notification_type'] ?? null,
            'public_id' => $payload['public_id'] ?? null,
        ]);

        return response()->json(['status' => 'ok'], 200);
    }
}
