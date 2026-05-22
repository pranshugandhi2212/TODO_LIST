<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('feedback', 'subject')) {
            Schema::table('feedback', function (Blueprint $table): void {
                $table->string('subject')->nullable()->after('email');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('feedback', 'subject')) {
            Schema::table('feedback', function (Blueprint $table): void {
                $table->dropColumn('subject');
            });
        }
    }
};
