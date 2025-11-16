<?php

use App\Enums\Measure;
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
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('purchase_id')
                ->constrained('purchases')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Purchase this line item belongs to');

            $table->foreignId('product_id')
                ->nullable()
                ->constrained('products')
                ->cascadeOnUpdate()
                ->nullOnDelete()
                ->comment('Reference to catalog product (nullable for ad-hoc entries)');

            $table->foreignId('category_id')
                ->nullable()
                ->constrained('categories')
                ->cascadeOnUpdate()
                ->nullOnDelete()
                ->comment('Explicit category snapshot for this line (optional)');

            $table->foreignId('brand_id')
                ->nullable()
                ->constrained('brands')
                ->cascadeOnUpdate()
                ->nullOnDelete()
                ->comment('Explicit brand snapshot for this line (optional)');

            $table->string('product_name_snapshot', 255)
                ->comment('Product name as shown on the receipt (snapshot for history)');

            $table->string('measure', 20)
                ->default(Measure::PIECE->value)
                ->comment('Unit of measure (piece or kilogram)');

            $table->decimal('quantity', 10, 3)
                ->default(0)
                ->comment('Quantity purchased (supports decimals for kg)');

            $table->decimal('unit_price_net', 12, 4)
                ->default(0)
                ->comment('Unit price before VAT');

            $table->decimal('unit_price_gross', 12, 4)
                ->default(0)
                ->comment('Unit price after VAT');

            $table->decimal('line_discount_amount', 12, 2)
                ->default(0)
                ->comment('Absolute discount applied to this line');

            $table->decimal('line_discount_percent', 5, 2)
                ->nullable()
                ->comment('Percentage discount applied (if available)');

            $table->decimal('vat_rate', 5, 2)
                ->default(0)
                ->comment('VAT percentage applied to this line (e.g., 19.00)');

            $table->decimal('vat_amount', 12, 2)
                ->default(0)
                ->comment('Total VAT amount for this line');

            $table->decimal('line_total_net', 12, 2)
                ->default(0)
                ->comment('Line total before VAT');

            $table->decimal('line_total_gross', 12, 2)
                ->default(0)
                ->comment('Line total after VAT');

            $table->text('notes')->nullable()->comment('Optional line-level notes');

            $table->timestamps();

            $table->index('purchase_id');
            $table->index('product_id');
            $table->index('category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
