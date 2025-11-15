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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique()->comment('Payment method name');
            $table->string('slug', 100)->unique()->comment('URL-friendly identifier');
            $table->string('category', 50)->default('other')->comment('Category grouping (card, cash, digital, etc.)');
            $table->text('description')->nullable()->comment('Optional description or hints');
            $table->unsignedTinyInteger('display_order')->default(0)->comment('Controls ordering in dropdowns');
            $table->boolean('is_active')->default(true)->comment('Whether payment method is active');
            $table->timestamps();

            // Indexes
            $table->index('category');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
