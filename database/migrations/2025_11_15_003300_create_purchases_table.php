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
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('shop_id')->constrained()->onDelete('restrict');
            $table->foreignId('shop_address_id')->constrained()->onDelete('restrict');

            $table->date('purchase_date');
            $table->string('currency', 3)->default('EUR');
            $table->enum('status', ['draft', 'confirmed', 'cancelled'])->default('confirmed');

            $table->unsignedInteger('subtotal')->default(0)->comment('Subtotal in cents');
            $table->unsignedInteger('tax_amount')->default(0)->comment('Tax amount in cents');
            $table->unsignedInteger('total_amount')->default(0)->comment('Total amount in cents');

            $table->text('notes')->nullable();
            $table->string('receipt_number')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('purchase_date');
            $table->index('status');
            $table->index(['shop_id', 'purchase_date']);
            $table->unique(['shop_id', 'receipt_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
