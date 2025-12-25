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
        Schema::create('purchase_receipt_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->string('disk', 50)->default('receipts');
            $table->string('path', 500);
            $table->string('original_filename', 255);
            $table->string('mime_type', 100);
            $table->unsignedInteger('size')->comment('File size in bytes');
            $table->string('checksum', 64)->nullable()->comment('SHA-256 hash for deduplication');

            $table->timestamps();
            $table->softDeletes();

            $table->index('purchase_id');
            $table->index('user_id');
            $table->index('checksum');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_receipt_files');
    }
};
