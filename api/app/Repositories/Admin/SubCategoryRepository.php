<?php
namespace App\Repositories\Admin;

use App\Models\SubCategory;
use Illuminate\Pagination\LengthAwarePaginator;

class SubCategoryRepository
{

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = SubCategory::query()
            ->select('id', 'category_id', 'parent_id', 'name', 'slug', 'des', 'order_num', 'image_url', 'image_public_id', 'status', 'level', 'created_at', 'updated_at')
            ->with([
                'category:id,name,slug',
                'parent:id,name,slug',
            ]);
        $search_txt = trim((string) ($filters['search_txt'] ?? ''));
        $sort_by = trim((string) ($filters['sort_by'] ?? 'latest'));
        $status = $filters['status'] ?? null;
        $per_page = (int) ($filters['per_page'] ?? 20);

        if ($search_txt !== '') {
            $query->where(function ($q) use ($search_txt) {
                $q->where('name', 'like', '%' . $search_txt . '%')
                    ->orWhere('slug', 'like', '%' . $search_txt . '%')
                    ->orWhereHas('category', function ($qc) use ($search_txt) {
                        $qc->where('name', 'like', '%' . $search_txt . '%');
                    })
                    ->orWhereHas('parent', function ($qp) use ($search_txt) {
                        $qp->where('name', 'like', '%' . $search_txt . '%');
                    })
                    ->orWhere('des', 'like', '%' . $search_txt . '%');
            });
        }


        //** status */
        if ($status !== null && $status !== '') {
            $query->where('status', filter_var($status, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? (bool) $status);
        }
        //** sort_by */
        match ($sort_by) {
            'latest' => $query->orderByDesc('sub_categories.created_at'),
            'oldest' => $query->orderBy('sub_categories.created_at'),
            'name_asc' => $query->orderBy('sub_categories.name'),
            'name_desc' => $query->orderByDesc('sub_categories.name'),
            default => $query->orderBy('sub_categories.order_num')
        };

        //** per_page */
        return $query->paginate($per_page);

    }

    public function create(array $payload): SubCategory
    {
        return SubCategory::query()->create($payload);
    }

    public function update(SubCategory $sub_category, array $payload): SubCategory
    {
        $sub_category->update($payload);
        return $sub_category;
    }

    public function destroy(SubCategory $sub_category): void
    {
        $sub_category->delete();

    }

    public function show(SubCategory $sub_category): SubCategory
    {
        return $sub_category->load([
            'category:id,name,slug',
            'parent:id,name,slug',
        ]);
    }


}
