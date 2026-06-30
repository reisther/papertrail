<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('adviser_expertise', function (Blueprint $table) {
            $table->id();

            // Adviser user
            $table->foreignId('adviser_id')
                  ->constrained('users')
                  ->onDelete('cascade');

            // Expertise fields
            $table->boolean('machine_learning')->default(false);
            $table->boolean('ai_integration')->default(false);
            $table->boolean('cybersecurity')->default(false);
            $table->boolean('iot')->default(false);
            $table->boolean('cloud_computing')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adviser_expertise');
    }
};