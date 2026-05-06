<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\AppSetting;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->seedModules();
        $this->seedRoles();
        $this->seedRolePermissions();

        User::factory()->create([
            "first_name" => "Super",
            "last_name" => "Admin",
            "gender" => "male",
            "user_name" => "super_admin",
            "phone" => "0202939833",
            "email" => "superadmin@gmail.com",
            "password" => "superadmin123",
            "role" => "super_admin"
        ]);

        $this->call([
            CatalogSeeder::class,
        ]);
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

        $rows = array_map(function (array $module) use ($now) {
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

        AppSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'app_name' => config('app.name'),
                'app_tagline' => 'Manage your store with clarity.',
                'support_email' => 'support@example.com',
                'support_phone' => '+85500000000',
                'business_address' => 'Phnom Penh, Cambodia',
                'currency_code' => 'USD',
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

        Cache::forget('admin:role-permissions:super_admin');
        Cache::forget('admin:role-permissions:admin');
        Cache::forget('app:settings:current');
        Cache::forget('admin:dashboard:summary');
    }
}
