<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

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
}
