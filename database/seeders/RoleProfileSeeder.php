<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleProfileSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $tableByRole = [
            'Student' => 'students',
            'Teacher' => 'advisers',
            'Admin' => 'admins',
            'Leader' => 'leaders',
        ];

        foreach (User::all() as $user) {
            $table = $tableByRole[$user->role] ?? null;

            if (!$table) {
                continue;
            }

            DB::table($table)->updateOrInsert(
                ['user_id' => $user->id],
                [
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $this->command->info('Role profile tables synced successfully.');
    }
}
