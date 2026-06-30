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
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->json('seen_by')->nullable()->after('edited_at'); // Array of user IDs who have seen the message
            $table->json('deleted_for_users')->nullable()->after('seen_by'); // Array of user IDs who deleted the message for themselves
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropColumn(['seen_by', 'deleted_for_users']);
        });
    }
};
