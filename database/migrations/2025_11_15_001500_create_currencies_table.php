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
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique()->comment('ISO 4217 alphabetic code');
            $table->char('numeric_code', 3)->nullable()->comment('ISO 4217 numeric code');
            $table->string('symbol', 10)->comment('Currency symbol');
            $table->string('name', 100)->comment('Full currency name');
            $table->boolean('is_active')->default(true)->comment('Whether currency is active');
            $table->timestamps();

            // Indexes
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
