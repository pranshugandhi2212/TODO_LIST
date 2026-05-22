<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->text('description')->nullable();

            $table->string('category')->default('General');
            $table->string('priority')->default('Medium');
            $table->string('assignee')->nullable();

            $table->date('due_date')->nullable();

            $table->integer('estimated_hours')->default(0);
            $table->integer('estimated_minutes')->default(0);

            $table->string('attachment')->nullable();

            $table->string('status')->default('pending');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};