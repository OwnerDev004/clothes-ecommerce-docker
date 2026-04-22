<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\Api\V1\Admin\DashboardService;
use App\Traits\ApiResponse;
use OpenApi\Attributes as OA;

class AnalyticsController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly DashboardService $dashboardService)
    {
    }

    #[OA\Get(
        path: '/api/v1/admin/analytics',
        tags: ['Admin/Analytics'],
        summary: 'Get admin analytics summary',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Analytics summary'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function index()
    {
        return $this->success(
            $this->dashboardService->summary(),
            'Analytics summary',
            200
        );
    }
}
