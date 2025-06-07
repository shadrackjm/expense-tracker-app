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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade'); // If user deleted, their expenses are too
            $table->foreignId('category_id')
                  ->constrained('categories')
                  ->onDelete('restrict'); // Don't delete category if expenses are linked
            $table->foreignId('payment_method_id')
                  ->nullable() // An expense might not always have a specific payment method recorded
                  ->constrained('payment_methods')
                  ->onDelete('set null'); // If payment method deleted, set to null
            $table->decimal('amount', 10, 2);
            $table->text('description')->nullable();
            $table->date('expense_date'); // Date when the expense occurred
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
