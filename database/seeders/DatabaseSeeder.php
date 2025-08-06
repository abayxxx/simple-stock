<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'admin', // Set role for the admin user
            'password' => bcrypt('password'), // Set a default password
        ]);

        $superAdmin = User::factory()->create([
            'name' => 'Super Admin User',
            'email' => 'superadmin@example.com',
            'role' => 'superadmin', // Set role for the super admin user
            'password' => bcrypt('superadmin'), // Set a default password
        ]);

        //Seeder products 5000 data
       Product::factory()->count(5000)->create();

    }
}
