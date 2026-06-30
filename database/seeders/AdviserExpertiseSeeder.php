<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdviserExpertiseSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('adviser_expertise')->insert([
            [
                'adviser_id' => 3,
                'machine_learning' => true,
                'ai_integration' => true,
                'cybersecurity' => false,
                'iot' => false,
                'cloud_computing' => true,
            ]
        ]);
    }
}