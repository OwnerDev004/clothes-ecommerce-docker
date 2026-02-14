<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductStoreRequest extends FormRequest
{

    public function authorize()
    {
        return true;
        // return auth()->guard('admin')->check();

    }

    public function rules()
    {
        return [
            "name" => "required|string|max:255|unique:user,name",
            "desc" => "nullable|string",
            "price" => "required|decimal:8,2|default:0",
            "category_id" => "required|integer",
            "dress_type_id" => "required|integer"
        ];
    }







}