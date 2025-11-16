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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->comment('Product name');
            $table->string('slug', 255)->unique()->comment('URL-friendly identifier');
            
            $table->foreignId('category_id')
                ->constrained('categories')
                ->cascadeOnUpdate()
                ->restrictOnDelete()
                ->comment('Product category');
            
            $table->foreignId('brand_id')
                ->nullable()
                ->constrained('brands')
                ->cascadeOnUpdate()
                ->nullOnDelete()
                ->comment('Product brand (nullable for generic/no-brand items)');
            
            $table->string('default_measure', 20)
                ->default(Measure::PIECE->value)
                ->comment('Default unit of measure for this product');
            
            $table->decimal('package_size', 10, 3)
                ->nullable()
                ->comment('Package size (e.g., 500 for 500g package)');
            
            $table->string('package_unit', 20)
                ->nullable()
                ->comment('Package unit (g, ml, etc.)');
            
            $table->text('description')->nullable()->comment('Product description');
            $table->string('barcode', 100)->nullable()->comment('EAN/UPC barcode');
            $table->boolean('is_active')->default(true)->comment('Active status');
            $table->timestamps();

            $table->index('category_id');
            $table->index('brand_id');
            $table->index('barcode');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
