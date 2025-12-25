<?php

namespace App\Models;

use App\DTO\Purchase\PurchaseReceiptFileData;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseReceiptFile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'purchase_id',
        'user_id',
        'disk',
        'path',
        'original_filename',
        'mime_type',
        'size',
        'checksum',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function toData(): PurchaseReceiptFileData
    {
        return new PurchaseReceiptFileData(
            id: $this->id,
            purchaseId: $this->purchase_id,
            originalFilename: $this->original_filename,
            mimeType: $this->mime_type,
            size: $this->size,
            uploadedAt: $this->created_at->toIso8601String(),
            downloadUrl: "/api/purchases/{$this->purchase_id}/attachments/{$this->id}/download",
        );
    }
}
