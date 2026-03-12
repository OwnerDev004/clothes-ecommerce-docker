<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Color;
use App\Models\DressType;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Size;
use App\Models\SubCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $categories = [
            ['name' => "Men's Clothing", 'des' => 'Shirts, pants, and outfits for men', 'slug' => 'mens-clothing'],
            ['name' => "Women's Clothing", 'des' => 'Dresses, tops, and outfits for women', 'slug' => 'womens-clothing'],
            ['name' => 'Accessories', 'des' => 'Bags, belts, caps, and more', 'slug' => 'accessories'],
            ['name' => 'Shoes', 'des' => 'Sneakers, loafers, and heels', 'slug' => 'shoes'],
        ];
        $categories = array_map(function ($row) use ($now) {
            return $row + ['created_at' => $now, 'updated_at' => $now];
        }, $categories);
        DB::table('categories')->upsert($categories, ['slug'], ['name', 'des', 'updated_at']);

        $dressTypes = [
            ['name' => 'Gym Dress', 'desc' => 'Workout and training outfits', 'slug' => 'gym-dress', 'sort_order' => 1, 'img' => 'default_empty'],
            ['name' => 'Party Dress', 'desc' => 'Event and celebration outfits', 'slug' => 'party-dress', 'sort_order' => 2, 'img' => 'default_empty'],
            ['name' => 'Sport Dress', 'desc' => 'Performance and sport outfits', 'slug' => 'sport-dress', 'sort_order' => 3, 'img' => 'default_empty'],
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

        $subCategories = [
            ['category_slug' => 'mens-clothing', 'name' => 'Men Clothes', 'slug' => 'men-clothes', 'des' => 'Subcategory for men clothing'],
            ['category_slug' => 'womens-clothing', 'name' => 'Women Clothes', 'slug' => 'women-clothes', 'des' => 'Subcategory for women clothing'],
            ['category_slug' => 'shoes', 'name' => 'Shoes', 'slug' => 'shoes-sub', 'des' => 'Subcategory for shoes'],
            ['category_slug' => 'accessories', 'name' => 'Accessories', 'slug' => 'accessories-sub', 'des' => 'Subcategory for accessories'],
        ];
        $subCategoryRows = array_map(function ($row) use ($categoriesBySlug, $now) {
            return [
                'category_id' => $categoriesBySlug[$row['category_slug']]->id,
                'name' => $row['name'],
                'slug' => $row['slug'],
                'des' => $row['des'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $subCategories);
        DB::table('sub_categories')->upsert($subCategoryRows, ['slug'], ['category_id', 'name', 'des', 'updated_at']);
        $subCategoriesBySlug = SubCategory::whereIn('slug', array_column($subCategoryRows, 'slug'))->get()->keyBy('slug');

        $products = [
            [
                'name' => 'Classic Cotton Tee',
                'desc' => 'Soft cotton t-shirt with a clean fit',
                'price' => 200.00,
                'category_slug' => 'mens-clothing',
                'sub_category_slug' => 'men-clothes',
                'dress_type_slug' => 'gym-dress',
            ],
            [
                'name' => 'Oxford Button Shirt',
                'desc' => 'Smart casual oxford shirt',
                'price' => 200.00,
                'category_slug' => 'mens-clothing',
                'sub_category_slug' => 'men-clothes',
                'dress_type_slug' => 'party-dress',
            ],
            [
                'name' => 'Slim Fit Jeans',
                'desc' => 'Dark wash slim fit jeans',
                'price' => 49.99,
                'category_slug' => 'mens-clothing',
                'sub_category_slug' => 'men-clothes',
                'dress_type_slug' => 'sport-dress',
            ],
            [
                'name' => 'Chino Pants',
                'desc' => 'Comfortable tapered chinos',
                'price' => 44.99,
                'category_slug' => 'mens-clothing',
                'sub_category_slug' => 'men-clothes',
                'dress_type_slug' => 'gym-dress',
            ],
            [
                'name' => 'Lightweight Windbreaker',
                'desc' => 'Packable windbreaker jacket',
                'price' => 59.99,
                'category_slug' => 'womens-clothing',
                'sub_category_slug' => 'women-clothes',
                'dress_type_slug' => 'sport-dress',
            ],
            [
                'name' => 'Denim Jacket',
                'desc' => 'Classic denim jacket',
                'price' => 79.99,
                'category_slug' => 'womens-clothing',
                'sub_category_slug' => 'women-clothes',
                'dress_type_slug' => 'party-dress',
            ],
            [
                'name' => 'Midi Summer Dress',
                'desc' => 'Flowy midi dress with prints',
                'price' => 69.99,
                'category_slug' => 'womens-clothing',
                'sub_category_slug' => 'women-clothes',
                'dress_type_slug' => 'gym-dress',
            ],
            [
                'name' => 'Satin Evening Dress',
                'desc' => 'Elegant satin dress for evenings',
                'price' => 129.99,
                'category_slug' => 'womens-clothing',
                'sub_category_slug' => 'women-clothes',
                'dress_type_slug' => 'party-dress',
            ],

        ];

        $targetProductCount = 50;
        $categorySequence = ['mens-clothing', 'womens-clothing', 'accessories', 'shoes'];
        $dressTypeSequence = ['gym-dress', 'party-dress', 'sport-dress'];
        $subCategoryByCategory = [
            'mens-clothing' => 'men-clothes',
            'womens-clothing' => 'women-clothes',
            'accessories' => 'accessories-sub',
            'shoes' => 'shoes-sub',
        ];
        $categoryProductNames = [
            'mens-clothing' => ['Crew Tee', 'Polo Shirt', 'Cargo Pants', 'Oxford Shirt', 'Denim Jeans'],
            'womens-clothing' => ['Wrap Dress', 'Blouse Top', 'Maxi Dress', 'Skirt Set', 'Knit Top'],
            'accessories' => ['Leather Belt', 'Crossbody Bag', 'Classic Cap', 'Sunglasses', 'Wallet'],
            'shoes' => ['Running Sneakers', 'Loafers', 'Canvas Shoes', 'Formal Shoes', 'Sandals'],
        ];
        $stylePrefixes = [
            'gym-dress' => 'Gym',
            'party-dress' => 'Party',
            'sport-dress' => 'Sport',
        ];

        $seedIndex = 1;
        while (count($products) < $targetProductCount) {
            $categorySlug = $categorySequence[($seedIndex - 1) % count($categorySequence)];
            $dressTypeSlug = $dressTypeSequence[($seedIndex - 1) % count($dressTypeSequence)];
            $subCategorySlug = $subCategoryByCategory[$categorySlug];
            $nameOptions = $categoryProductNames[$categorySlug];
            $baseName = $nameOptions[($seedIndex - 1) % count($nameOptions)];
            $prefix = $stylePrefixes[$dressTypeSlug];

            $products[] = [
                'name' => sprintf('%s %s %02d', $prefix, $baseName, $seedIndex),
                'desc' => sprintf('%s %s designed for daily comfort and style.', $prefix, strtolower($baseName)),
                'price' => round(22 + (($seedIndex * 7) % 95) + (($seedIndex % 3) * 0.99), 2),
                'category_slug' => $categorySlug,
                'sub_category_slug' => $subCategorySlug,
                'dress_type_slug' => $dressTypeSlug,
            ];

            $seedIndex++;
        }

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
                'sub_category_id' => $subCategoriesBySlug[$product['sub_category_slug']]->id,
                'dress_type_id' => $dressTypesBySlug[$product['dress_type_slug']]->id,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('products')->upsert(
            $productRows,
            ['slug'],
            ['sku', 'name', 'desc', 'price', 'category_id', 'sub_category_id', 'dress_type_id', 'updated_at']
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
                    'sell_price' => $product->price,
                    'cost_price' => round($product->price * 0.6, 2),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('product_variants')->upsert(
            $variants,
            ['product_id', 'color_id', 'size_id'],
            ['stock_quantity', 'sell_price', 'cost_price', 'updated_at']
        );
    }
}
