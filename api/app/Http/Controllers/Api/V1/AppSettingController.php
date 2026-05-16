<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
<<<<<<< HEAD
use App\Models\AppSetting;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Request;
=======
use App\Repositories\AppSettingRepository;
use App\Traits\ApiResponse;
>>>>>>> feature/pd-and-appsetting
use OpenAPi\Attributes as OA;

class AppSettingController extends Controller
{
    use ApiResponse;

<<<<<<< HEAD
=======
    public function __construct(private readonly AppSettingRepository $settingRepository)
    {
    }

>>>>>>> feature/pd-and-appsetting
    //index
    #[OA\Get(
        path: '/api/v1/app_setting',
        tags: ['AppSetting'],
        summary: 'Get App Setting ',
        responses: [
            new OA\Response(response: 200, description: 'app_setting list'),
        ]
    )]
<<<<<<< HEAD
    public function index(Request $request)
    {
        $app_setting = AppSetting::query()->select('shipping_rates', 'shipping_fee', 'app_name', 'currency_code')->get();
        return $this->success($app_setting, 'app_setting_list', 200);
    }
}
=======
    public function index()
    {
        return $this->success(collect([$this->settingRepository->current()]), 'app_setting_list', 200);
    }
}
>>>>>>> feature/pd-and-appsetting
