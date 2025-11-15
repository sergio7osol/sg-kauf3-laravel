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
        Schema::create('shop_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')
                ->constrained('shops')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('country', 100)->default('Germany')->comment('Country of the shop location');
            $table->string('postal_code', 20)->comment('Postal/ZIP code');
            $table->string('city', 120)->comment('City name');
            $table->string('street', 150)->comment('Street name');
            $table->string('house_number', 25)->comment('House or building number');
            $table->boolean('is_primary')->default(false)->comment('Marks main/default address per shop');
            $table->unsignedTinyInteger('display_order')->default(0)->comment('Controls ordering per shop');
            $table->boolean('is_active')->default(true)->comment('Whether address can be selected');
            $table->timestamps();

            $table->unique(['shop_id', 'postal_code', 'street', 'house_number'], 'shop_addresses_unique_location');
            $table->index('postal_code');
            $table->index('city');
            $table->index(['shop_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_addresses');
    }
};
