<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Request;
use OpenAPi\Attributes as OA;

class AppSettingController extends Controller
{
    use ApiResponse;

    //index
    #[OA\Get(
        path: '/api/v1/app_setting',
        tags: ['AppSetting'],
        summary: 'Get App Setting ',
        responses: [
            new OA\Response(response: 200, description: 'app_setting list'),
        ]
    )]
    public function index(Request $request)
    {
        $app_setting = AppSetting::query()->select('shipping_rates', 'shipping_fee', 'app_name', 'currency_code')->get();
        return $this->success($app_setting, 'app_setting_list', 200);
    }
}