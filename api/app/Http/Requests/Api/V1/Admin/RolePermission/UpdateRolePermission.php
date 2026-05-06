<?php

namespace App\Http\Requests\Api\V1\Admin\RolePermission;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRolePermission extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

        ];
    }
}