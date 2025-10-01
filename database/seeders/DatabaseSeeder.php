<?php

namespace Database\Seeders;

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

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        // Seed an admin user with all required fields
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('adminpassword'),
            'role' => 'admin',
            'about' => 'Admin user for system management.',
            'phone' => '08000000000',
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'address' => 'Admin HQ, City',
            'religion' => 'Other',
            'email_verification_code' => 'ADM123',
            'email_verification_code_expires_at' => now()->addDays(1),
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'country' => 'Nigeria',
            'region' => 'Lagos',
            'working_hours' => '8am-6pm',
        ]);
    }
}
