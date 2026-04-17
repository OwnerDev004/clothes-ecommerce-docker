<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Collection;
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
            ['name' => "Men", 'des' => 'Menswear', 'slug' => 'men'],
            ['name' => "Women", 'des' => 'Womenswear', 'slug' => 'women'],
            ['name' => 'Boys', 'des' => 'Boys clothing', 'slug' => 'boys'],
            ['name' => 'Girls', 'des' => 'Girls clothing', 'slug' => 'girls'],
        ];
        $categories = array_map(function ($row) use ($now) {
            return $row + ['created_at' => $now, 'updated_at' => $now];
        }, $categories);
        DB::table('categories')->upsert($categories, ['slug'], ['name', 'des', 'updated_at']);

        $categoriesBySlug = Category::whereIn('slug', array_column($categories, 'slug'))->get()->keyBy('slug');

        $collections = [
            ['category_slug' => 'men', 'name' => 'Men Essentials', 'desc' => 'Core menswear staples', 'slug' => 'men-essentials', 'sort_order' => 0, 'status' => 'published', 'img' => 'default_empty'],
            ['category_slug' => 'men', 'name' => 'Men Active', 'desc' => 'Sport and training', 'slug' => 'men-active', 'sort_order' => 1, 'status' => 'published', 'img' => 'default_empty'],
            ['category_slug' => 'women', 'name' => 'Women Essentials', 'desc' => 'Core womenswear staples', 'slug' => 'women-essentials', 'sort_order' => 2, 'status' => 'published', 'img' => 'default_empty'],
            ['category_slug' => 'women', 'name' => 'Women Party', 'desc' => 'Event and celebration outfits', 'slug' => 'women-party', 'sort_order' => 3, 'status' => 'draft', 'img' => 'default_empty'],
            ['category_slug' => 'boys', 'name' => 'Boys Play', 'desc' => 'Everyday playwear', 'slug' => 'boys-play', 'sort_order' => 4, 'status' => 'draft', 'img' => 'default_empty'],
            ['category_slug' => 'girls', 'name' => 'Girls Play', 'desc' => 'Everyday playwear', 'slug' => 'girls-play', 'sort_order' => 5, 'status' => 'published', 'img' => 'default_empty'],
        ];
        $collections = array_map(function ($row) use ($now, $categoriesBySlug) {
            return [
                'category_id' => $categoriesBySlug[$row['category_slug']]->id,
                'name' => $row['name'],
                'desc' => $row['desc'],
                'slug' => $row['slug'],
                'sort_order' => $row['sort_order'],
                'status' => $row['status'],
                'img' => $row['img'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $collections);
        DB::table('collections')->upsert($collections, ['slug'], ['category_id', 'name', 'desc', 'sort_order', 'img', 'updated_at']);

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

        $collectionsBySlug = Collection::whereIn('slug', array_column($collections, 'slug'))->get()->keyBy('slug');

        $subCategories = [];
        $subCategoryTypes = [
            ['name' => 'Accessories', 'slug' => 'accessories', 'des' => 'Accessories'],
            ['name' => 'Clothes', 'slug' => 'clothes', 'des' => 'Clothes'],
            ['name' => 'Shoes', 'slug' => 'shoes', 'des' => 'Shoes'],
        ];
        foreach (array_column($categories, 'slug') as $categorySlug) {
            foreach ($subCategoryTypes as $sub) {
                $subCategories[] = [
                    'category_slug' => $categorySlug,
                    'name' => $sub['name'],
                    'slug' => $categorySlug . '-' . $sub['slug'],
                    'des' => $sub['des'],
                ];
            }
        }
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
                'price' => 20.00,
                'category_slug' => 'men',
                'sub_category_slug' => 'men-clothes',
                'collection_slug' => 'men-essentials',
            ],
            [
                'name' => 'Oxford Button Shirt',
                'desc' => 'Smart casual oxford shirt',
                'price' => 29.00,
                'category_slug' => 'men',
                'sub_category_slug' => 'men-clothes',
                'collection_slug' => 'men-essentials',
            ],
            [
                'name' => 'Performance Joggers',
                'desc' => 'Breathable joggers for training',
                'price' => 35.00,
                'category_slug' => 'men',
                'sub_category_slug' => 'men-clothes',
                'collection_slug' => 'men-active',
            ],
            [
                'name' => 'Women Wrap Dress',
                'desc' => 'Elegant wrap dress',
                'price' => 45.00,
                'category_slug' => 'women',
                'sub_category_slug' => 'women-clothes',
                'collection_slug' => 'women-party',
            ],
            [
                'name' => 'Women Blouse',
                'desc' => 'Lightweight everyday blouse',
                'price' => 32.00,
                'category_slug' => 'women',
                'sub_category_slug' => 'women-clothes',
                'collection_slug' => 'women-essentials',
            ],
            [
                'name' => 'Boys Hoodie',
                'desc' => 'Soft fleece hoodie',
                'price' => 25.00,
                'category_slug' => 'boys',
                'sub_category_slug' => 'boys-clothes',
                'collection_slug' => 'boys-play',
            ],
            [
                'name' => 'Girls Sneakers',
                'desc' => 'Everyday sneakers',
                'price' => 28.00,
                'category_slug' => 'girls',
                'sub_category_slug' => 'girls-shoes',
                'collection_slug' => 'girls-play',
            ],
        ];

        $targetProductCount = 50;
        $categorySequence = ['men', 'women', 'boys', 'girls'];
        $collectionSequence = ['men-essentials', 'men-active', 'women-essentials', 'women-party', 'boys-play', 'girls-play'];
        $subCategoryByCategory = [
            'men' => 'men-clothes',
            'women' => 'women-clothes',
            'boys' => 'boys-clothes',
            'girls' => 'girls-clothes',
        ];
        $categoryProductNames = [
            'men' => ['Crew Tee', 'Polo Shirt', 'Cargo Pants', 'Oxford Shirt', 'Denim Jeans'],
            'women' => ['Wrap Dress', 'Blouse Top', 'Maxi Dress', 'Skirt Set', 'Knit Top'],
            'boys' => ['Hoodie', 'Joggers', 'Graphic Tee', 'Shorts', 'Sweatshirt'],
            'girls' => ['Leggings', 'Tunic', 'Skirt', 'Cardigan', 'Dress'],
        ];
        $collectionPrefixes = [
            'men-essentials' => 'Men',
            'men-active' => 'Active',
            'women-essentials' => 'Women',
            'women-party' => 'Party',
            'boys-play' => 'Boys',
            'girls-play' => 'Girls',
        ];

        $seedIndex = 1;
        while (count($products) < $targetProductCount) {
            $categorySlug = $categorySequence[($seedIndex - 1) % count($categorySequence)];
            $collectionSlug = $collectionSequence[($seedIndex - 1) % count($collectionSequence)];
            $subCategorySlug = $subCategoryByCategory[$categorySlug];
            $nameOptions = $categoryProductNames[$categorySlug];
            $baseName = $nameOptions[($seedIndex - 1) % count($nameOptions)];
            $prefix = $collectionPrefixes[$collectionSlug];

            $products[] = [
                'name' => sprintf('%s %s %02d', $prefix, $baseName, $seedIndex),
                'desc' => sprintf('%s %s designed for daily comfort and style.', $prefix, strtolower($baseName)),
                'price' => round(22 + (($seedIndex * 7) % 95) + (($seedIndex % 3) * 0.99), 2),
                'category_slug' => $categorySlug,
                'sub_category_slug' => $subCategorySlug,
                'collection_slug' => $collectionSlug,
            ];

            $seedIndex++;
        }

        $productRows = [];
        $productCollectionMap = [];
        foreach ($products as $product) {
            $slug = Str::slug($product['name']);
            $productCollectionMap[$slug] = $product['collection_slug'];
            $productRows[] = [
                'sku' => 'SKU-' . strtoupper(substr(md5($slug), 0, 8)),
                'slug' => $slug,
                'name' => $product['name'],
                'desc' => $product['desc'],
                'price' => $product['price'],
                'category_id' => $categoriesBySlug[$product['category_slug']]->id,
                'sub_category_id' => $subCategoriesBySlug[$product['sub_category_slug']]->id,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('products')->upsert(
            $productRows,
            ['slug'],
            ['sku', 'name', 'desc', 'price', 'category_id', 'sub_category_id', 'updated_at']
        );

        $productsBySlug = Product::whereIn('slug', array_column($productRows, 'slug'))->get()->keyBy('slug');
        $collectionLinks = [];
        foreach ($productRows as $row) {
            $slug = $row['slug'];
            $collectionSlug = $productCollectionMap[$slug] ?? null;
            if (!$collectionSlug || !isset($collectionsBySlug[$collectionSlug])) {
                continue;
            }
            $collectionLinks[] = [
                'collection_id' => $collectionsBySlug[$collectionSlug]->id,
                'product_id' => $productsBySlug[$slug]->id,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if (!empty($collectionLinks)) {
            DB::table('collection_product')->upsert(
                $collectionLinks,
                ['collection_id', 'product_id'],
                ['updated_at']
            );
        }
        $sizesByName = Size::whereIn('name', array_column($sizes, 'name'))->get()->keyBy('name');

        $variantCombos = [
            ['#000000', 'S'],
            ['#000000', 'M'],
            ['#ffffff', 'M'],
            ['#ffffff', 'L'],
        ];

        $variants = [];
        foreach ($productRows as $row) {
            $product = $productsBySlug[$row['slug']];
            foreach ($variantCombos as [$colorValue, $sizeName]) {
                $variants[] = [
                    'product_id' => $product->id,
                    'color' => $colorValue,
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
            ['product_id', 'color', 'size_id'],
            ['stock_quantity', 'sell_price', 'cost_price', 'updated_at']
        );
    }
}
