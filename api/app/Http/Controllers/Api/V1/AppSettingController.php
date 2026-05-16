<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Repositories\AppSettingRepository;
use App\Traits\ApiResponse;
use OpenAPi\Attributes as OA;

class AppSettingController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly AppSettingRepository $settingRepository)
    {
    }

    //index
    #[OA\Get(
        path: '/api/v1/app_setting',
        tags: ['AppSetting'],
        summary: 'Get App Setting ',
        responses: [
            new OA\Response(response: 200, description: 'app_setting list'),
        ]
    )]
    public function index()
    {
        return $this->success(collect([$this->settingRepository->current()]), 'app_setting_list', 200);
    }
}
