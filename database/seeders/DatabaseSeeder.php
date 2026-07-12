<?php

namespace Database\Seeders;

use App\Models\CibilReport;
use App\Models\LoanApplication;
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
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
            ]
        );

        // Create 10 admin/test users with a known password for development
        for ($i = 1; $i <= 10; $i++) {
            User::firstOrCreate(
                ['email' => "admin{$i}@gmail.com"],
                [
                    'name' => "Admin {$i}",
                    'password' => bcrypt('admin@123'),
                ]
            );
        }

        LoanApplication::factory()->count(20)->create();
        CibilReport::factory()->count(20)->create();
    }
}
