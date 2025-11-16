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

            $table->date('purchase_date')->index()->comment('Calendar date of the purchase');
            $table->time('purchase_time')->nullable()->comment('Clock time of the purchase (optional)');

            $table->foreignId('currency_id')
                ->constrained('currencies')
                ->cascadeOnUpdate()
                ->restrictOnDelete()
                ->comment('Currency used for this purchase');

            $table->foreignId('payment_method_id')
                ->constrained('payment_methods')
                ->cascadeOnUpdate()
                ->restrictOnDelete()
                ->comment('Payment method used for this purchase');

            $table->foreignId('shop_id')
                ->nullable()
                ->constrained('shops')
                ->cascadeOnUpdate()
                ->nullOnDelete()
                ->comment('Shop where purchase was made (nullable for online-only or future payees)');

            $table->foreignId('shop_address_id')
                ->nullable()
                ->constrained('shop_addresses')
                ->cascadeOnUpdate()
                ->nullOnDelete()
                ->comment('Specific shop location (nullable for online purchases)');

            $table->string('receipt_number', 100)->nullable()->comment('Optional receipt or invoice identifier');

            $table->decimal('subtotal_net', 12, 2)->default(0)->comment('Sum of line totals before VAT');
            $table->decimal('total_vat', 12, 2)->default(0)->comment('Total VAT collected for this purchase');
            $table->decimal('total_gross', 12, 2)->default(0)->comment('Total amount paid (net + VAT)');

            $table->string('vat_summary', 255)->nullable()->comment('Serialized summary of VAT rates applied (e.g., "19%: 12.34; 7%: 4.56")');

            $table->text('notes')->nullable()->comment('Optional free-form notes about the purchase');

            $table->timestamps();

            $table->index(['shop_id', 'purchase_date']);
            $table->index(['currency_id', 'purchase_date']);
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
