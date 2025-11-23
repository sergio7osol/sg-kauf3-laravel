<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('line_number')->default(1);
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();

            $table->string('description');
            $table->decimal('quantity', 10, 3);
            $table->unsignedInteger('unit_price');
            $table->unsignedInteger('line_amount');

            $table->decimal('tax_rate', 5, 2);
            $table->unsignedInteger('tax_amount');

            $table->decimal('discount_percent', 5, 2)->nullable();
            $table->unsignedInteger('discount_amount')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['purchase_id', 'line_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_lines');
    }
};
