<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('student_number', 50)->nullable()->after('section');
        });

        Schema::table('adviser_expertise', function (Blueprint $table) {
            $table->boolean('data_analytics')->default(false)->after('cloud_computing');
            $table->boolean('web_development')->default(false)->after('data_analytics');
            $table->boolean('mobile_development')->default(false)->after('web_development');
            $table->boolean('database_systems')->default(false)->after('mobile_development');
            $table->boolean('networking')->default(false)->after('database_systems');
        });
    }

    public function down(): void
    {
        Schema::table('adviser_expertise', function (Blueprint $table) {
            $table->dropColumn([
                'data_analytics',
                'web_development',
                'mobile_development',
                'database_systems',
                'networking',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('student_number');
        });
    }
};
