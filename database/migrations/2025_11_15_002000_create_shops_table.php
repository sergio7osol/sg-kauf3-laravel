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
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->unique()->comment('Shop/store name');
            $table->string('slug', 255)->unique()->comment('URL-friendly identifier');
            $table->string('type', 50)->default('in_store')->comment('Preferred purchase channel: in_store, online, hybrid');
            $table->string('country', 100)->nullable()->comment('Default country for the shop');
            $table->unsignedTinyInteger('display_order')->default(0)->comment('Controls ordering in dropdowns');
            $table->boolean('is_active')->default(true)->comment('Whether shop is active');
            $table->timestamps();

            // Indexes
            $table->index('is_active');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shops');
    }
};
