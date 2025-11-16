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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('Category name');
            $table->string('slug', 100)->unique()->comment('URL-friendly identifier');
            
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('categories')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Parent category for hierarchy (e.g., Dairy under Groceries)');
            
            $table->text('description')->nullable()->comment('Category description');
            $table->string('icon', 50)->nullable()->comment('Icon identifier for UI');
            $table->unsignedTinyInteger('display_order')->default(0)->comment('Sort order');
            $table->boolean('is_active')->default(true)->comment('Active status');
            $table->timestamps();

            $table->index(['parent_id', 'display_order']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
