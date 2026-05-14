<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\Category;
use App\Models\Module;
use App\Models\Role;
use App\Models\Size;
use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProductionSeeder extends Seeder
{
    /**
     * Seed the minimum production-ready data.
     *
     * This keeps demo products out of production while still creating the
     * taxonomy and access control data the app needs to work normally.
     */
    public function run(): void
    {
        $this->seedModules();
        $this->seedRoles();
        $this->seedRolePermissions();
        $this->seedSizes();
        $this->seedCategories();
        $this->seedAppSetting();
        $this->seedAdminUserFromEnv();
    }

    private function seedModules(): void
    {
        $now = now();

        $modules = [
            ['name' => 'Dashboard', 'slug' => 'dashboard'],
            ['name' => 'Analytics', 'slug' => 'analytics'],
            ['name' => 'Products', 'slug' => 'products'],
            ['name' => 'Product Variants', 'slug' => 'product_variants'],
            ['name' => 'Orders', 'slug' => 'orders'],
            ['name' => 'Purchases', 'slug' => 'purchases'],
            ['name' => 'Promotions', 'slug' => 'promotions'],
            ['name' => 'Categories', 'slug' => 'categories'],
            ['name' => 'Customers', 'slug' => 'customers'],
            ['name' => 'Brands', 'slug' => 'brands'],
            ['name' => 'Collections', 'slug' => 'collections'],
            ['name' => 'Roles', 'slug' => 'roles'],
            ['name' => 'Setting', 'slug' => 'setting'],
            ['name' => 'Admins', 'slug' => 'admins'],
        ];

        $rows = array_map(static function (array $module) use ($now) {
            return $module + [
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $modules);

        DB::table('modules')->upsert($rows, ['slug'], ['name', 'updated_at']);
    }

    private function seedRoles(): void
    {
        Role::query()->updateOrCreate(
            ['slug' => 'super_admin'],
            [
                'name' => 'Super Admin',
                'desc' => 'Full access to every admin module.',
                'status' => true,
                'is_system' => true,
            ]
        );

        Role::query()->updateOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Admin',
                'desc' => 'Standard admin role.',
                'status' => true,
                'is_system' => false,
            ]
        );
    }

    private function seedRolePermissions(): void
    {
        $modules = Module::query()->pluck('id', 'slug');
        $now = now();

        $rolePermissionMap = [
            'super_admin' => array_fill_keys($modules->keys()->all(), ['view', 'create', 'edit', 'delete']),
            'admin' => [
                'dashboard' => ['view'],
                'roles' => ['view', 'create', 'edit', 'delete'],
                'admins' => ['view', 'create', 'edit', 'delete'],
                'setting' => ['view', 'edit'],
                'categories' => ['view', 'create', 'edit', 'delete'],
                'collections' => ['view', 'create', 'edit', 'delete'],
                'products' => ['view', 'create', 'edit', 'delete'],
                'product_variants' => ['view', 'create', 'edit', 'delete'],
                'orders' => ['view', 'edit'],
                'customers' => ['view'],
            ],
        ];

        $rows = [];

        foreach ($rolePermissionMap as $roleSlug => $permissionMap) {
            $role = Role::query()->where('slug', $roleSlug)->first();
            if (!$role) {
                continue;
            }

            foreach ($permissionMap as $moduleSlug => $actions) {
                $moduleId = $modules->get($moduleSlug);
                if (!$moduleId) {
                    continue;
                }

                $rows[] = [
                    'role_id' => $role->id,
                    'module_id' => $moduleId,
                    'permissions' => json_encode(array_values(array_unique($actions))),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (!empty($rows)) {
            DB::table('role_permissions')->upsert($rows, ['role_id', 'module_id'], ['permissions', 'updated_at']);
        }

        Cache::forget('admin:role-permissions:super_admin');
        Cache::forget('admin:role-permissions:admin');
    }

    private function seedSizes(): void
    {
        $now = now();

        $sizes = [
            ['name' => 'XS', 'sort_order' => 1],
            ['name' => 'S', 'sort_order' => 2],
            ['name' => 'M', 'sort_order' => 3],
            ['name' => 'L', 'sort_order' => 4],
            ['name' => 'XL', 'sort_order' => 5],
            ['name' => 'XXL', 'sort_order' => 6],
        ];

        $rows = array_map(static function (array $size) use ($now) {
            return $size + [
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $sizes);

        DB::table('sizes')->upsert($rows, ['name'], ['sort_order', 'updated_at']);
    }

    private function seedCategories(): void
    {
        $now = now();

        $categories = [
            ['name' => 'Men', 'des' => 'Menswear', 'slug' => 'men'],
            ['name' => 'Women', 'des' => 'Womenswear', 'slug' => 'women'],
            ['name' => 'Boys', 'des' => 'Boys clothing', 'slug' => 'boys'],
            ['name' => 'Girls', 'des' => 'Girls clothing', 'slug' => 'girls'],
        ];

        $categoryRows = array_map(static function (array $category) use ($now) {
            return $category + [
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $categories);

        DB::table('categories')->upsert($categoryRows, ['slug'], ['name', 'des', 'updated_at']);

        $categoriesBySlug = Category::query()
            ->whereIn('slug', array_column($categoryRows, 'slug'))
            ->get()
            ->keyBy('slug');

        $subCategoryTypes = [
            ['name' => 'Clothes', 'slug' => 'clothes', 'des' => 'Clothing items'],
            ['name' => 'Shoes', 'slug' => 'shoes', 'des' => 'Footwear'],
            ['name' => 'Accessories', 'slug' => 'accessories', 'des' => 'Accessories and extras'],
        ];

        $subCategoryRows = [];
        foreach (array_keys($categoriesBySlug->all()) as $categorySlug) {
            foreach ($subCategoryTypes as $subCategory) {
                $subCategoryRows[] = [
                    'category_id' => $categoriesBySlug[$categorySlug]->id,
                    'name' => $subCategory['name'],
                    'slug' => $categorySlug . '-' . $subCategory['slug'],
                    'des' => $subCategory['des'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('sub_categories')->upsert(
            $subCategoryRows,
            ['slug'],
            ['category_id', 'name', 'des', 'updated_at']
        );

        Cache::forget('app:settings:current');
    }

    private function seedAppSetting(): void
    {
        AppSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'app_name' => config('app.name'),
                'app_tagline' => 'Manage your store with clarity.',
                'support_email' => 'support@example.com',
                'support_phone' => '+85500000000',
                'business_address' => 'Phnom Penh, Cambodia',
                'default_currency_code' => 'USD',
                'exchange_rate' => 4000,
                'shipping_fee' => 3.50,
                'free_shipping_threshold' => 50,
                'low_stock_threshold' => 20,
                'tax_rate' => 0,
                'shipping_rates' => [
                    ['province' => 'Phnom Penh', 'fee' => 1.50],
                    ['province' => 'Kandal', 'fee' => 2.00],
                    ['province' => 'Siem Reap', 'fee' => 2.50],
                    ['province' => 'Battambang', 'fee' => 2.50],
                    ['province' => 'Preah Sihanouk', 'fee' => 3.00],
                ],
            ]
        );

        Cache::forget('app:settings:current');
        Cache::forget('admin:dashboard:summary');
    }

    private function seedAdminUserFromEnv(): void
    {
        $email = trim((string) env('SEED_SUPER_ADMIN_EMAIL', ''));
        $password = trim((string) env('SEED_SUPER_ADMIN_PASSWORD', ''));

        if ($email === '' || $password === '') {
            return;
        }

        $firstName = trim((string) env('SEED_SUPER_ADMIN_FIRST_NAME', 'Super'));
        $lastName = trim((string) env('SEED_SUPER_ADMIN_LAST_NAME', 'Admin'));
        $userName = trim((string) env('SEED_SUPER_ADMIN_USERNAME', 'super_admin'));
        $phone = trim((string) env('SEED_SUPER_ADMIN_PHONE', ''));
        $gender = strtolower(trim((string) env('SEED_SUPER_ADMIN_GENDER', 'male')));

        if (!in_array($gender, ['male', 'female'], true)) {
            $gender = 'male';
        }

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'gender' => $gender,
                'user_name' => $userName,
                'phone' => $phone !== '' ? $phone : null,
                'role' => 'super_admin',
                'password' => $password,
            ]
        );
    }
}
