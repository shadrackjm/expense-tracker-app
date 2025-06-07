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
        Schema::create('incomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade'); // If user deleted, their budgets are too
            $table->foreignId('category_id')
                  ->nullable() // A budget can be for a specific category or overall (if null)
                  ->constrained('categories')
                  ->onDelete('cascade'); // If a category is deleted, budgets for it are too
            $table->decimal('amount', 10, 2);
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();
        });

        Schema::create('recurring_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade'); // If user deleted, their recurring transactions are too
            $table->foreignId('category_id')
                  ->nullable() // A recurring transaction can be uncategorized
                  ->constrained('categories')
                  ->onDelete('set null'); // Don't delete category if recurring transactions are linked
            $table->foreignId('payment_method_id')
                  ->nullable() // For recurring expenses, if a specific payment method is used
                  ->constrained('payment_methods')
                  ->onDelete('set null'); // If payment method deleted, set to null
            $table->decimal('amount', 10, 2);
            $table->string('type'); // 'expense' or 'income'
            $table->string('description')->nullable();
            $table->string('frequency'); // e.g., 'daily', 'weekly', 'monthly', 'yearly'
            $table->date('start_date');
            $table->date('end_date')->nullable(); // Can be null for ongoing recurring transactions
            $table->date('next_occurrence_date'); // To track when the next transaction is due
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incomes');
        Schema::dropIfExists('recurring_transactions');
    }
};
