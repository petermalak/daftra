<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::create([
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('1234'), // Use a strong password in production!
            'role' => 'admin', // or 'manager', etc. if your app uses roles
        ]);

        // Seed inventory data
        // $this->call([
        //     InventorySeeder::class,
        // ]);
    }
}
