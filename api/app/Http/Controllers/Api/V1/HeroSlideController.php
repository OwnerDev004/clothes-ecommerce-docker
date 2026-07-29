<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Admin\HeroSlideResource;
use App\Models\HeroSlide;
use App\Traits\ApiResponse;

class HeroSlideController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $slides = HeroSlide::query()
            ->where('status', true)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();

        return $this->success(HeroSlideResource::collection($slides), 'Hero slides list');
    }
}
