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
        responses: [
            new OA\Response(response: 200, description: 'Settings loaded'),
        ],
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
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['app_name', 'default_currency_code', 'exchange_rate', 'shipping_fee', 'free_shipping_threshold', 'low_stock_threshold', 'tax_rate'],
                properties: [
                    new OA\Property(property: 'app_name', type: 'string', maxLength: 255),
                    new OA\Property(property: 'app_tagline', type: 'string', maxLength: 255, nullable: true),
                    new OA\Property(property: 'support_email', type: 'string', format: 'email', maxLength: 255, nullable: true),
                    new OA\Property(property: 'support_phone', type: 'string', maxLength: 50, nullable: true),
                    new OA\Property(property: 'business_address', type: 'string', nullable: true),
                    new OA\Property(property: 'default_currency_code', type: 'string', maxLength: 10),
                    new OA\Property(property: 'exchange_rate', type: 'number', format: 'float', minimum: 0),
                    new OA\Property(property: 'shipping_fee', type: 'number', format: 'float', minimum: 0),
                    new OA\Property(property: 'free_shipping_threshold', type: 'number', format: 'float', minimum: 0),
                    new OA\Property(property: 'low_stock_threshold', type: 'integer', minimum: 0),
                    new OA\Property(property: 'tax_rate', type: 'number', format: 'float', minimum: 0),
                    new OA\Property(
                        property: 'shipping_rates',
                        type: 'array',
                        nullable: true,
                        items: new OA\Items(
                            type: 'object',
                            required: ['province', 'fee'],
                            properties: [
                                new OA\Property(property: 'province', type: 'string', maxLength: 255),
                                new OA\Property(property: 'fee', type: 'number', format: 'float', minimum: 0),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Settings updated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
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
