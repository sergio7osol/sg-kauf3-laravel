<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_id',
        'line_number',
        'product_id',
        'description',
        'quantity',
        'unit_price',
        'line_amount',
        'tax_rate',
        'tax_amount',
        'discount_percent',
        'discount_amount',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'integer',
        'line_amount' => 'integer',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'integer',
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'integer',
    ];

    /**
     * Relationships
     */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Automatically calculate line amounts, discounts, and tax on save.
     * This ensures totals are always consistent with quantity/price/rates.
     *
     * Discount precedence:
     * 1. If discount_amount is explicitly provided (> 0), use it directly
     * 2. Else if discount_percent > 0, compute discount_amount from it
     * 3. Otherwise, no discount
     */
    protected static function booted()
    {
        static::saving(function (PurchaseLine $line) {
            // Calculate gross line amount (quantity × unit_price)
            $grossAmount = (int) (($line->quantity ?? 0) * ($line->unit_price ?? 0));

            // Determine discount amount based on what was provided
            // isDirty checks if discount_amount was explicitly set in this save operation
            if ($line->isDirty('discount_amount') && $line->discount_amount > 0) {
                // Explicit discount amount provided - cap at gross amount to prevent negative totals
                $line->discount_amount = min((int) $line->discount_amount, $grossAmount);
            } elseif ($line->discount_percent && $line->discount_percent > 0) {
                // Calculate from percentage
                $line->discount_amount = (int) ($grossAmount * ($line->discount_percent / 100));
            } else {
                $line->discount_amount = 0;
            }

            // Net line amount = gross - discount
            $line->line_amount = $grossAmount - $line->discount_amount;

            // Calculate tax on the net line amount
            $line->tax_amount = (int) ($line->line_amount * (($line->tax_rate ?? 0) / 100));
        });
    }

    /**
     * Convert model to DTO for API responses.
     */
    public function toData(): \App\DTO\Purchase\PurchaseLineData
    {
        return new \App\DTO\Purchase\PurchaseLineData(
            id: $this->id,
            purchaseId: $this->purchase_id,
            lineNumber: $this->line_number,
            productId: $this->product_id,
            description: $this->description,
            quantity: (float) $this->quantity,
            unitPrice: $this->unit_price,
            lineAmount: $this->line_amount,
            taxRate: (float) $this->tax_rate,
            taxAmount: $this->tax_amount,
            discountPercent: $this->discount_percent ? (float) $this->discount_percent : null,
            discountAmount: $this->discount_amount,
            notes: $this->notes,
        );
    }
}
