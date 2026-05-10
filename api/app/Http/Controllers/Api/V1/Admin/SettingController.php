<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Setting\UpdateSettingRequest;
use App\Http\Resources\Api\V1\Admin\SettingResource;
use App\Repositories\AppSettingRepository;
use App\Traits\ApiResponse;
use OpenApi\Attributes as OA;
use Str;

class SettingController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly AppSettingRepository $settingRepository)
    {
    }

    #[OA\Get(
        path: '/api/v1/admin/setting',
        tags: ['Admin/Setting'],
        summary: 'Get admin settings',
        security: [['bearerAuth' => []]],
    )]
    public function index()
    {
        return $this->success(new SettingResource($this->settingRepository->current()), 'Settings loaded');
    }

    #[OA\Put(
        path: '/api/v1/admin/setting',
        tags: ['Admin/Setting'],
        summary: 'Update admin settings',
        security: [['bearerAuth' => []]],
    )]
    public function update(UpdateSettingRequest $request)
    {
        $payload = $request->validated();
        $payload['shipping_rates'] = array_values(array_filter(
            array_map(
                fn(array $row) => [
                    'slug' => Str::slug($row['province'], '-'),
                    'province' => trim((string) ($row['province'] ?? '')),
                    'fee' => (float) ($row['fee'] ?? 0),
                ],
                $payload['shipping_rates'] ?? []
            ),
            fn(array $row) => $row['province'] !== ''
        ));

        $setting = $this->settingRepository->update($payload);

        return $this->success(new SettingResource($setting), 'Settings updated');
    }
}
