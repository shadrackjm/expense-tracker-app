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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->nullable() // Categories can be global (null user_id) or user-specific
                  ->constrained('users')
                  ->onDelete('cascade'); // If a user is deleted, their custom categories are too
            $table->string('name');
            $table->string('type')->default('expense'); // 'expense' or 'income'
            $table->text('description')->nullable();
            $table->timestamps();

            // Ensure category name is unique per user, or globally if user_id is null
            $table->unique(['user_id', 'name']);
            // Add a unique index for global categories if user_id is null
            $table->unique(['name', 'type'], 'unique_global_category_name_type')->whereNotNull('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
