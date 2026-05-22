<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            if (!Schema::hasColumn('tasks', 'project')) {
                $table->string('project')->nullable()->after('assignee');
            }

            if (!Schema::hasColumn('tasks', 'department')) {
                $table->string('department')->nullable()->after('project');
            }

            if (!Schema::hasColumn('tasks', 'client_name')) {
                $table->string('client_name')->nullable()->after('department');
            }

            if (!Schema::hasColumn('tasks', 'location')) {
                $table->string('location')->nullable()->after('client_name');
            }

            if (!Schema::hasColumn('tasks', 'status_note')) {
                $table->text('status_note')->nullable()->after('location');
            }

            if (!Schema::hasColumn('tasks', 'lane')) {
                $table->string('lane')->nullable()->after('status_note');
            }

            if (!Schema::hasColumn('tasks', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('status');
            }

            if (!Schema::hasColumn('tasks', 'attachments')) {
                $table->json('attachments')->nullable()->after('attachment');
            }

            if (!Schema::hasColumn('tasks', 'checkpoints')) {
                $table->json('checkpoints')->nullable()->after('attachments');
            }

            if (!Schema::hasColumn('tasks', 'tags')) {
                $table->json('tags')->nullable()->after('checkpoints');
            }

            if (!Schema::hasColumn('tasks', 'comments')) {
                $table->json('comments')->nullable()->after('tags');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            if (Schema::hasColumn('tasks', 'comments')) {
                $table->dropColumn('comments');
            }

            if (Schema::hasColumn('tasks', 'tags')) {
                $table->dropColumn('tags');
            }

            if (Schema::hasColumn('tasks', 'checkpoints')) {
                $table->dropColumn('checkpoints');
            }

            if (Schema::hasColumn('tasks', 'attachments')) {
                $table->dropColumn('attachments');
            }

            if (Schema::hasColumn('tasks', 'completed_at')) {
                $table->dropColumn('completed_at');
            }

            if (Schema::hasColumn('tasks', 'lane')) {
                $table->dropColumn('lane');
            }

            if (Schema::hasColumn('tasks', 'status_note')) {
                $table->dropColumn('status_note');
            }

            if (Schema::hasColumn('tasks', 'location')) {
                $table->dropColumn('location');
            }

            if (Schema::hasColumn('tasks', 'client_name')) {
                $table->dropColumn('client_name');
            }

            if (Schema::hasColumn('tasks', 'department')) {
                $table->dropColumn('department');
            }

            if (Schema::hasColumn('tasks', 'project')) {
                $table->dropColumn('project');
            }
        });
    }
};
