<?php

namespace App\Models;

use App\DTO\Shop\ShopData;
use App\Enums\CountryCode;
use App\Enums\PurchaseChannel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shop extends Model
{
    /** @use HasFactory<\Database\Factories\ShopFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'type',
        'country',
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
            'type' => PurchaseChannel::class,
            'country' => CountryCode::class,
            'is_active' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    /**
     * Get the addresses for this shop.
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(ShopAddress::class)
            ->orderBy('display_order')
            ->orderBy('id');
    }

    /**
     * Alias for factories expecting `shopAddresses` relation name.
     */
    public function shopAddresses(): HasMany
    {
        return $this->addresses();
    }

    /**
     * Get the purchases made at this shop.
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    /**
     * Convert model to DTO for API responses.
     */
    public function toData(): ShopData
    {
        return new ShopData(
            id: $this->id,
            name: $this->name,
            slug: $this->slug,
            type: $this->type,
            country: $this->country,
            displayOrder: $this->display_order,
            isActive: $this->is_active,
        );
    }
}
