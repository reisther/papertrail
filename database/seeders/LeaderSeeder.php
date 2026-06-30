<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LeaderSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'leader@papertrail.com'],
            [
                'firstname' => 'Alex',
                'middlename' => 'Lee',
                'lastname' => 'Leader',
                'campus' => 'Main Campus',
                'course' => 'Computer Science',
                'section' => 'A',
                'id_document_path' => null,
                'status' => 'Verified',
                'role' => 'Leader',
                'password' => Hash::make('leader123'),
            ]
        );

        $this->command->info('Leader: leader@papertrail.com / leader123');
    }
}
