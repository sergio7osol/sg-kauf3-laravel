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
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique()->comment('Brand name');
            $table->string('slug', 100)->unique()->comment('URL-friendly identifier');
            $table->text('description')->nullable()->comment('Brand description');
            $table->string('logo_url', 255)->nullable()->comment('Brand logo URL');
            $table->boolean('is_active')->default(true)->comment('Active status');
            $table->timestamps();

            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};
