<?php

namespace App\DTO\Shop;

use App\Enums\CountryCode;
use App\Enums\PurchaseChannel;

/**
 * Shop DTO - represents a shop/store entity for API responses
 */
readonly class ShopData
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public PurchaseChannel $type,
        public ?CountryCode $country,
        public int $displayOrder,
        public bool $isActive,
    ) {}

    /**
     * Convert to array for JSON serialization
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type->value,
            'country' => $this->country?->value,
            'displayOrder' => $this->displayOrder,
            'isActive' => $this->isActive,
        ];
    }
}
