<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $modules = Module::query()
            ->select('id', 'name', 'slug', 'created_at', 'updated_at')
            ->orderBy('name')
            ->get();

        return $this->success($modules, 'Modules list');
    }
}
