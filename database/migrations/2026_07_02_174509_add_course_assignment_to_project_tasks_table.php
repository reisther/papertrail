<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->string('assignment_course')->nullable()->after('adviser_id');
            $table->string('course_task_group_id')->nullable()->after('assignment_course');
            $table->index(['adviser_id', 'assignment_course']);
            $table->index('course_task_group_id');
        });
    }

    public function down(): void
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->dropIndex(['adviser_id', 'assignment_course']);
            $table->dropIndex(['course_task_group_id']);
            $table->dropColumn(['assignment_course', 'course_task_group_id']);
        });
    }
};
