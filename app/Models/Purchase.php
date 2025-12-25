<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Clear receipt_number when soft-deleting to free up the unique constraint.
     * This allows re-importing the same receipt after accidental deletion.
     */
    protected static function booted()
    {
        static::deleting(function (Purchase $purchase) {
            // Only clear on soft-delete, not force-delete
            if (!$purchase->isForceDeleting()) {
                $purchase->receipt_number = null;
                $purchase->saveQuietly(); // Avoid triggering other events
            }
        });
    }

    protected $fillable = [
        'user_id',
        'shop_id',
        'shop_address_id',
        'user_payment_method_id',
        'purchase_date',
        'purchase_time',
        'currency',
        'status',
        'subtotal',
        'tax_amount',
        'total_amount',
        'notes',
        'receipt_number',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'subtotal' => 'integer',
        'tax_amount' => 'integer',
        'total_amount' => 'integer',
        // 'status' => PurchaseStatus::class, // Uncomment when PurchaseStatus enum is created
    ];

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function shopAddress(): BelongsTo
    {
        return $this->belongsTo(ShopAddress::class);
    }

    public function userPaymentMethod(): BelongsTo
    {
        return $this->belongsTo(UserPaymentMethod::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseLine::class)->orderBy('line_number');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(PurchaseReceiptFile::class);
    }

    /**
     * Recalculate purchase totals from line items.
     * NOTE: Call this within a DB transaction when saving lines to ensure atomicity.
     */
    public function recalculateTotals(): void
    {
        $this->subtotal = $this->lines->sum('line_amount');
        $this->tax_amount = $this->lines->sum('tax_amount');
        $this->total_amount = $this->subtotal + $this->tax_amount;
        $this->save();
    }

    /**
     * Convert model to DTO for API responses.
     */
    public function toData(bool $includeLines = false): \App\DTO\Purchase\PurchaseData
    {
        $lines = [];
        $attachments = [];
        
        if ($includeLines && $this->relationLoaded('lines')) {
            $lines = $this->lines->map(fn($line) => $line->toData())->all();
        }

        if ($this->relationLoaded('attachments')) {
            $attachments = $this->attachments->map(fn($file) => $file->toData())->all();
        }

        $shop = $this->relationLoaded('shop') ? $this->shop->toData() : null;
        $shopAddress = $this->relationLoaded('shopAddress') ? $this->shopAddress->toData() : null;
        $userPaymentMethod = $this->relationLoaded('userPaymentMethod') && $this->userPaymentMethod
            ? $this->userPaymentMethod->toData()
            : null;

        return new \App\DTO\Purchase\PurchaseData(
            id: $this->id,
            userId: $this->user_id,
            shopId: $this->shop_id,
            shopAddressId: $this->shop_address_id,
            userPaymentMethodId: $this->user_payment_method_id,
            purchaseDate: $this->purchase_date->toDateString(),
            purchaseTime: $this->purchase_time,
            currency: $this->currency,
            status: $this->status,
            subtotal: $this->subtotal,
            taxAmount: $this->tax_amount,
            totalAmount: $this->total_amount,
            notes: $this->notes,
            receiptNumber: $this->receipt_number,
            lines: $lines,
            attachments: $attachments,
            shop: $shop,
            shopAddress: $shopAddress,
            userPaymentMethod: $userPaymentMethod,
        );
    }
}
