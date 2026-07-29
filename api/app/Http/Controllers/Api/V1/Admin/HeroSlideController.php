<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\HeroSlide\ListHeroSlideRequest;
use App\Http\Requests\Api\V1\Admin\HeroSlide\StoreHeroSlideRequest;
use App\Http\Requests\Api\V1\Admin\HeroSlide\UpdateHeroSlideRequest;
use App\Http\Resources\Api\V1\Admin\HeroSlideResource;
use App\Models\HeroSlide;
use App\Services\Api\V1\Admin\HeroSlideService;
use App\Traits\ApiResponse;

class HeroSlideController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly HeroSlideService $heroSlideService)
    {
    }

    public function index(ListHeroSlideRequest $request)
    {
        $heroSlides = $this->heroSlideService->paginate($request->validated());
        $heroSlides->setCollection($heroSlides->getCollection()->map(fn($slide) => HeroSlideResource::make($slide)->resolve()));

        return $this->paginate($heroSlides, 'Hero slides list');
    }

    public function show(HeroSlide $heroSlide)
    {
        return $this->success(new HeroSlideResource($heroSlide), 'Hero slide detail');
    }

    public function store(StoreHeroSlideRequest $request)
    {
        $heroSlide = $this->heroSlideService->store($request->validated(), $request->file('image'));

        return $this->created(new HeroSlideResource($heroSlide), 'Hero slide created');
    }

    public function update(UpdateHeroSlideRequest $request, HeroSlide $heroSlide)
    {
        $heroSlide = $this->heroSlideService->update($heroSlide, $request->validated(), $request->file('image'));

        return $this->success(new HeroSlideResource($heroSlide->fresh()), 'Hero slide updated');
    }

    public function destroy(HeroSlide $heroSlide)
    {
        $this->heroSlideService->destroy($heroSlide);

        return $this->success(null, 'Hero slide deleted');
    }
}
