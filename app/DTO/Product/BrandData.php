<?php

namespace App\DTO\Product;

/**
 * Brand DTO - represents a product brand/manufacturer
 */
readonly class BrandData
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public ?string $description,
        public ?string $logoUrl,
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
            'description' => $this->description,
            'logoUrl' => $this->logoUrl,
            'isActive' => $this->isActive,
        ];
    }
}
