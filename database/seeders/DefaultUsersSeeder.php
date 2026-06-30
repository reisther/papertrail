<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DefaultUsersSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Create Admin User
        User::updateOrCreate(['email' => 'admin@papertrail.com'], [
            'firstname' => 'Admin',
            'middlename' => '',
            'lastname' => 'User',
            'campus' => 'Main Campus',
            'course' => 'Administration',
            'section' => 'N/A',
            'id_document_path' => null,
            'status' => 'Verified',
            'email' => 'admin@papertrail.com',
            'role' => 'Admin',
            'password' => Hash::make('admin123'),
        ]);

        // Create Student User
        User::updateOrCreate(['email' => 'student@papertrail.com'], [
            'firstname' => 'John',
            'middlename' => 'Doe',
            'lastname' => 'Student',
            'campus' => 'Main Campus',
            'course' => 'Computer Science',
            'section' => 'A',
            'id_document_path' => null,
            'status' => 'Verified',
            'email' => 'student@papertrail.com',
            'role' => 'Student',
            'password' => Hash::make('student123'),
        ]);

        // Create Teacher User
        User::updateOrCreate(['email' => 'teacher@papertrail.com'], [
            'firstname' => 'Jane',
            'middlename' => 'Smith',
            'lastname' => 'Teacher',
            'campus' => 'Main Campus',
            'course' => 'Computer Science Department',
            'section' => 'Faculty',
            'id_document_path' => null,
            'status' => 'Verified',
            'email' => 'teacher@papertrail.com',
            'role' => 'Teacher',
            'password' => Hash::make('teacher123'),
        ]);

        $this->command->info('Default users created successfully!');
        $this->command->info('Admin: admin@papertrail.com / admin123');
        $this->command->info('Student: student@papertrail.com / student123');
        $this->command->info('Teacher: teacher@papertrail.com / teacher123');
    }
}
