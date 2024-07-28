<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Author;
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
            'id_number' => 'ADMIN001',
            'role' => 'admin',
            'first_name' => 'Admin',
            'middle_name' => '',
            'last_name' => 'User',
            'course' => 'N/A',
            'year_level' => 'N/A',
            'gender' => 'Other',
            'profile_picture' => null,
            'address' => '123 Admin Street, Admin City',
            'birthday' => '1990-01-01',
            'contact_number' => '1234567890',
            'position' => 'System Administrator',
            'department' => 'IT Department',
            'email' => 'admin@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('12345678'),
        ]);

        // Create Student User
        // User::create([
        //     'role' => 'student',
        //     'first_name' => 'John',
        //     'middle_name' => 'Doe',
        //     'last_name' => 'Smith',
        //     'course' => 'Computer Science',
        //     'year_level' => '3rd Year',
        //     'gender' => 'Male',
        //     'profile_picture' => null,
        //     'address' => '456 Student Ave, College Town',
        //     'birthday' => '2000-05-15',
        //     'contact_number' => '9876543210',
        //     'position' => null,
        //     'department' => 'School of Computing',
        //     'email' => 'john.smith@student.example.com',
        //     'email_verified_at' => now(),
        //     'password' => Hash::make('12345678'),
        // ]);
        
        User::factory()->count(50)->create();
        Author::factory()->count(10)->create();
        Category::factory()->count(10)->create();
    }
}
