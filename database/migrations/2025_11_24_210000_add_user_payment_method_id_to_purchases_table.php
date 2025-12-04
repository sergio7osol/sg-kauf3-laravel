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
        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('user_payment_method_id')
                ->nullable()
                ->after('shop_address_id')
                ->constrained('user_payment_methods')
                ->nullOnDelete()
                ->comment('User-defined payment method used for this purchase');

            $table->index('user_payment_method_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['user_payment_method_id']);
            $table->dropIndex(['user_payment_method_id']);
            $table->dropColumn('user_payment_method_id');
        });
    }
};
