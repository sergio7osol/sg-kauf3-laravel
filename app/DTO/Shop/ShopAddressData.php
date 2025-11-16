<?php

namespace App\DTO\Shop;

use App\Enums\CountryCode;

/**
 * Shop Address DTO - represents a physical shop location
 * Maps to the old frontend "Address" interface
 */
readonly class ShopAddressData
{
    public function __construct(
        public int $id,
        public int $shopId,
        public CountryCode $country,
        public string $postalCode,
        public string $city,
        public string $street,
        public string $houseNumber,
        public bool $isPrimary,
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
            'shopId' => $this->shopId,
            'country' => $this->country->value,
            'postalCode' => $this->postalCode,
            'city' => $this->city,
            'street' => $this->street,
            'houseNumber' => $this->houseNumber,
            'isPrimary' => $this->isPrimary,
            'displayOrder' => $this->displayOrder,
            'isActive' => $this->isActive,
        ];
    }

    /**
     * Get legacy format compatible with old frontend Address type
     */
    public function toLegacyFormat(): array
    {
        return [
            'country' => $this->country->value,
            'index' => $this->postalCode,
            'city' => $this->city,
            'street' => $this->street,
            'houseNumber' => $this->houseNumber,
        ];
    }
}
