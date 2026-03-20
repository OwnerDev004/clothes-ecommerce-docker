<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FiveCollectionsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $categories = [
            ['name' => 'Men', 'des' => 'Menswear', 'slug' => 'men'],
            ['name' => 'Women', 'des' => 'Womenswear', 'slug' => 'women'],
            ['name' => 'Boys', 'des' => 'Boys clothing', 'slug' => 'boys'],
            ['name' => 'Girls', 'des' => 'Girls clothing', 'slug' => 'girls'],
        ];

        $categories = array_map(function ($row) use ($now) {
            return $row + ['created_at' => $now, 'updated_at' => $now];
        }, $categories);

        DB::table('categories')->upsert($categories, ['slug'], ['name', 'des', 'updated_at']);
        $categoriesBySlug = Category::whereIn('slug', array_column($categories, 'slug'))->get()->keyBy('slug');

        $collections = [
            ['category_slug' => 'men', 'name' => 'Men Essentials', 'desc' => 'Core menswear staples', 'slug' => 'men-essentials', 'sort_order' => 1, 'img' => 'default_empty'],
            ['category_slug' => 'men', 'name' => 'Men Active', 'desc' => 'Sport and training', 'slug' => 'men-active', 'sort_order' => 2, 'img' => 'default_empty'],
            ['category_slug' => 'women', 'name' => 'Women Essentials', 'desc' => 'Core womenswear staples', 'slug' => 'women-essentials', 'sort_order' => 1, 'img' => 'default_empty'],
            ['category_slug' => 'women', 'name' => 'Women Party', 'desc' => 'Event and celebration outfits', 'slug' => 'women-party', 'sort_order' => 2, 'img' => 'default_empty'],
            ['category_slug' => 'boys', 'name' => 'Boys Play', 'desc' => 'Everyday playwear', 'slug' => 'boys-play', 'sort_order' => 1, 'img' => 'default_empty'],
        ];

        $rows = array_map(function ($row) use ($categoriesBySlug, $now) {
            return [
                'category_id' => $categoriesBySlug[$row['category_slug']]->id,
                'name' => $row['name'],
                'desc' => $row['desc'],
                'slug' => $row['slug'],
                'sort_order' => $row['sort_order'],
                'img' => $row['img'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $collections);

        DB::table('collections')->upsert(
            $rows,
            ['slug'],
            ['category_id', 'name', 'desc', 'sort_order', 'img', 'updated_at']
        );
    }
}
