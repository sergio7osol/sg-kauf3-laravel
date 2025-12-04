<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'notes',
        'is_active',
        'payment_method_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * Convert model to DTO for API responses.
     */
    public function toData(): \App\DTO\UserPaymentMethodData
    {
        return new \App\DTO\UserPaymentMethodData(
            id: $this->id,
            userId: $this->user_id,
            name: $this->name,
            notes: $this->notes,
            isActive: $this->is_active,
            paymentMethodId: $this->payment_method_id,
        );
    }
}
