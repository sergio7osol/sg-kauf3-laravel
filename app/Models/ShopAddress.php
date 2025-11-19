<?php

namespace App\Models;

use App\DTO\Shop\ShopAddressData;
use App\Enums\CountryCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopAddress extends Model
{
    /** @use HasFactory<\Database\Factories\ShopAddressFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'shop_id',
        'country',
        'postal_code',
        'city',
        'street',
        'house_number',
        'is_primary',
        'display_order',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'country' => CountryCode::class,
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    /**
     * Get the shop that owns this address.
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Get the purchases made at this address.
     */
    public function purchases(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Purchase::class, 'shop_address_id');
    }

    /**
     * Convert model to DTO for API responses.
     */
    public function toData(): ShopAddressData
    {
        return new ShopAddressData(
            id: $this->id,
            shopId: $this->shop_id,
            country: $this->country,
            postalCode: $this->postal_code,
            city: $this->city,
            street: $this->street,
            houseNumber: $this->house_number,
            isPrimary: $this->is_primary,
            displayOrder: $this->display_order,
            isActive: $this->is_active,
        );
    }
}
