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
        Schema::create('user_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade')
                ->comment('Owner of this payment method');
            $table->string('name', 100)->comment('User-defined label for the payment method');
            $table->text('notes')->nullable()->comment('Optional user notes');
            $table->boolean('is_active')->default(true)->comment('Whether this method is currently active');
            $table->foreignId('payment_method_id')
                ->nullable()
                ->constrained('payment_methods')
                ->nullOnDelete()
                ->comment('Optional reference to canonical payment method for analytics');
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('is_active');
            $table->unique(['user_id', 'name'], 'user_payment_method_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_payment_methods');
    }
};
