<?php

namespace App\Repositories\Admin;

use App\Models\HeroSlide;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class HeroSlideRepository
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = HeroSlide::query()
            ->select('id', 'title', 'subtitle', 'description', 'image_url', 'image_public_id', 'gradient', 'link_url', 'link_text', 'sort_order', 'status', 'created_at', 'updated_at');

        $search = trim((string) ($filters['search_txt'] ?? ''));
        $sortBy = trim((string) ($filters['sort_by'] ?? 'latest'));
        $status = $filters['status'] ?? null;

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('subtitle', 'like', '%' . $search . '%');
            });
        }

        if ($status !== null && $status !== '') {
            $query->where('status', filter_var($status, FILTER_VALIDATE_BOOLEAN));
        }

        match ($sortBy) {
            'oldest' => $query->orderBy('id'),
            'sort_order' => $query->orderBy('sort_order'),
            default => $query->orderByDesc('id'),
        };

        $perPage = (int) ($filters['per_page'] ?? 20);

        return $query->paginate($perPage);
    }

    public function create(array $payload): HeroSlide
    {
        return HeroSlide::query()->create($payload);
    }

    public function update(HeroSlide $heroSlide, array $payload): HeroSlide
    {
        $heroSlide->update($payload);
        return $heroSlide;
    }

    public function delete(HeroSlide $heroSlide): void
    {
        $heroSlide->delete();
    }
}
