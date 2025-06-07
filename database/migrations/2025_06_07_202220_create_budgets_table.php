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
       Schema::create('budgets', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')
          ->constrained('users')
          ->onDelete('cascade');
    $table->foreignId('category_id')
          ->nullable() // A budget can be for a specific category or overall (if null)
          ->constrained('categories')
          ->onDelete('cascade'); // If a category is deleted, budgets for it are too
    $table->decimal('amount', 10, 2);
    $table->date('start_date'); // Crucial for period-based budgeting
    $table->date('end_date');   // Crucial for period-based budgeting
    $table->timestamps();

    // Ensure no overlapping budgets for the same user and category
    $table->unique(['user_id', 'category_id', 'start_date', 'end_date'], 'unique_user_category_budget_period');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
