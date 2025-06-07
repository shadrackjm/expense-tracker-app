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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Each setting belongs to a user
            $table->string('key'); // e.g., 'currency_code', 'email_notifications_enabled'
            $table->text('value')->nullable(); // Storing the setting's value
            $table->string('type')->default('general'); // Categorize settings (e.g., 'currency', 'email', 'display')
            $table->timestamps();

            // Ensure unique settings key per user
            $table->unique(['user_id', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
