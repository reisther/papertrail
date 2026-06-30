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
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('verified_at')->nullable()->after('status');
            $table->foreignId('verified_by')->nullable()->constrained('users')->after('verified_at');
            $table->text('admin_notes')->nullable()->after('verified_by');
            $table->timestamp('rejected_at')->nullable()->after('admin_notes');
            $table->foreignId('rejected_by')->nullable()->constrained('users')->after('rejected_at');
            $table->text('rejection_reason')->nullable()->after('rejected_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropForeign(['rejected_by']);
            $table->dropColumn([
                'verified_at',
                'verified_by',
                'admin_notes',
                'rejected_at',
                'rejected_by',
                'rejection_reason'
            ]);
        });
    }
};
