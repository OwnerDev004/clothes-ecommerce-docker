<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Pusher\PushNotifications\PushNotifications;

class BeamsAuthController extends Controller
{
    use ApiResponse;

    #[OA\Get(
        path: '/api/v1/beams/auth',
        tags: ['Beams'],
        summary: 'Generate Beams auth token',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'user_id',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string'),
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Beams token generated'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Configuration error'),
        ]
    )]
    public function auth(Request $request)
    {
        $customer = auth()->guard('customer')->user();
        if (!$customer) {
            return $this->error('Unauthorized', 401);
        }

        $userId = trim((string) $request->query('user_id', ''));
        if ($userId === '') {
            return $this->error('Missing user_id', 422);
        }

        if ($userId !== (string) $customer->id) {
            return $this->error('Forbidden', 403);
        }

        $instanceId = trim((string) config('services.beams.instance_id', ''));
        $secretKey = trim((string) config('services.beams.secret_key', ''));
        if ($instanceId === '' || $secretKey === '') {
            return $this->error('Beams is not configured', 422);
        }

        $beamsClient = new PushNotifications([
            'instanceId' => $instanceId,
            'secretKey' => $secretKey,
        ]);

        $token = $beamsClient->generateToken($userId);

        return $this->success([
            'token' => $token['token'] ?? null,
        ], 'Beams token generated');
    }
}
