<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Color;
use App\Models\DressType;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Size;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $categories = [
            ['name' => 'Tops', 'des' => 'Shirts, tees, and blouses', 'slug' => 'tops'],
            ['name' => 'Bottoms', 'des' => 'Jeans, pants, and skirts', 'slug' => 'bottoms'],
            ['name' => 'Outerwear', 'des' => 'Jackets and coats', 'slug' => 'outerwear'],
            ['name' => 'Dresses', 'des' => 'Casual and formal dresses', 'slug' => 'dresses'],
        ];
        $categories = array_map(function ($row) use ($now) {
            return $row + ['created_at' => $now, 'updated_at' => $now];
        }, $categories);
        DB::table('categories')->upsert($categories, ['slug'], ['name', 'des', 'updated_at']);

        $dressTypes = [
            ['name' => 'Casual', 'desc' => 'Everyday wear', 'slug' => 'casual', 'sort_order' => 1, 'img' => 'default_empty'],
            ['name' => 'Formal', 'desc' => 'Office and events', 'slug' => 'formal', 'sort_order' => 2, 'img' => 'default_empty'],
            ['name' => 'Sport', 'desc' => 'Activewear', 'slug' => 'sport', 'sort_order' => 3, 'img' => 'default_empty'],
            ['name' => 'Streetwear', 'desc' => 'Urban style', 'slug' => 'streetwear', 'sort_order' => 4, 'img' => 'default_empty'],
        ];
        $dressTypes = array_map(function ($row) use ($now) {
            return $row + ['created_at' => $now, 'updated_at' => $now];
        }, $dressTypes);
        DB::table('dress_types')->upsert($dressTypes, ['slug'], ['name', 'desc', 'sort_order', 'img', 'updated_at']);

        $colors = [
            ['name' => 'Black', 'hex_code' => '#000000'],
            ['name' => 'White', 'hex_code' => '#FFFFFF'],
            ['name' => 'Red', 'hex_code' => '#E53935'],
            ['name' => 'Blue', 'hex_code' => '#1E88E5'],
            ['name' => 'Green', 'hex_code' => '#43A047'],
        ];
        $colors = array_map(function ($row) use ($now) {
            return $row + ['created_at' => $now, 'updated_at' => $now];
        }, $colors);
        DB::table('colors')->upsert($colors, ['hex_code'], ['name', 'updated_at']);

        $sizes = [
            ['name' => 'XS', 'sort_order' => 1],
            ['name' => 'S', 'sort_order' => 2],
            ['name' => 'M', 'sort_order' => 3],
            ['name' => 'L', 'sort_order' => 4],
            ['name' => 'XL', 'sort_order' => 5],
        ];
        $sizes = array_map(function ($row) use ($now) {
            return $row + ['created_at' => $now, 'updated_at' => $now];
        }, $sizes);
        DB::table('sizes')->upsert($sizes, ['name'], ['sort_order', 'updated_at']);

        $categoriesBySlug = Category::whereIn('slug', array_column($categories, 'slug'))->get()->keyBy('slug');
        $dressTypesBySlug = DressType::whereIn('slug', array_column($dressTypes, 'slug'))->get()->keyBy('slug');

        $products = [
            [
                'name' => 'Classic Cotton Tee',
                'desc' => 'Soft cotton t-shirt with a clean fit',
                'price' => 19.99,
                'category_slug' => 'tops',
                'dress_type_slug' => 'casual',
            ],
            [
                'name' => 'Oxford Button Shirt',
                'desc' => 'Smart casual oxford shirt',
                'price' => 39.99,
                'category_slug' => 'tops',
                'dress_type_slug' => 'formal',
            ],
            [
                'name' => 'Slim Fit Jeans',
                'desc' => 'Dark wash slim fit jeans',
                'price' => 49.99,
                'category_slug' => 'bottoms',
                'dress_type_slug' => 'streetwear',
            ],
            [
                'name' => 'Chino Pants',
                'desc' => 'Comfortable tapered chinos',
                'price' => 44.99,
                'category_slug' => 'bottoms',
                'dress_type_slug' => 'casual',
            ],
            [
                'name' => 'Lightweight Windbreaker',
                'desc' => 'Packable windbreaker jacket',
                'price' => 59.99,
                'category_slug' => 'outerwear',
                'dress_type_slug' => 'sport',
            ],
            [
                'name' => 'Denim Jacket',
                'desc' => 'Classic denim jacket',
                'price' => 79.99,
                'category_slug' => 'outerwear',
                'dress_type_slug' => 'streetwear',
            ],
            [
                'name' => 'Midi Summer Dress',
                'desc' => 'Flowy midi dress with prints',
                'price' => 69.99,
                'category_slug' => 'dresses',
                'dress_type_slug' => 'casual',
            ],
            [
                'name' => 'Satin Evening Dress',
                'desc' => 'Elegant satin dress for evenings',
                'price' => 129.99,
                'category_slug' => 'dresses',
                'dress_type_slug' => 'formal',
            ],
        ];

        $productRows = [];
        foreach ($products as $product) {
            $slug = Str::slug($product['name']);
            $productRows[] = [
                'sku' => 'SKU-' . strtoupper(substr(md5($slug), 0, 8)),
                'slug' => $slug,
                'name' => $product['name'],
                'desc' => $product['desc'],
                'price' => $product['price'],
                'category_id' => $categoriesBySlug[$product['category_slug']]->id,
                'dress_type_id' => $dressTypesBySlug[$product['dress_type_slug']]->id,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('products')->upsert(
            $productRows,
            ['slug'],
            ['sku', 'name', 'desc', 'price', 'category_id', 'dress_type_id', 'updated_at']
        );

        $productsBySlug = Product::whereIn('slug', array_column($productRows, 'slug'))->get()->keyBy('slug');
        $colorsByName = Color::whereIn('name', array_column($colors, 'name'))->get()->keyBy('name');
        $sizesByName = Size::whereIn('name', array_column($sizes, 'name'))->get()->keyBy('name');

        $variantCombos = [
            ['Black', 'S'],
            ['Black', 'M'],
            ['White', 'M'],
            ['White', 'L'],
        ];

        $variants = [];
        foreach ($productRows as $row) {
            $product = $productsBySlug[$row['slug']];
            foreach ($variantCombos as [$colorName, $sizeName]) {
                $variants[] = [
                    'product_id' => $product->id,
                    'color_id' => $colorsByName[$colorName]->id,
                    'size_id' => $sizesByName[$sizeName]->id,
                    'stock_quantity' => 20,
                    'price' => $product->price,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('product_variants')->upsert(
            $variants,
            ['product_id', 'color_id', 'size_id'],
            ['stock_quantity', 'price', 'updated_at']
        );
    }
}
