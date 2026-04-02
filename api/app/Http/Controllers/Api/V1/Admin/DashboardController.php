<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\Api\V1\Admin\DashboardService;
use App\Traits\ApiResponse;
use OpenApi\Attributes as OA;

class DashboardController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly DashboardService $dashboardService)
    {
    }

    #[OA\Get(
        path: '/api/v1/admin/dashboard',
        tags: ['Admin/Dashboard'],
        summary: 'Get admin dashboard summary',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Dashboard summary'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function index()
    {
        return $this->success(
            $this->dashboardService->summary(),
            'Dashboard summary',
            200
        );
    }
}
