<?php

namespace App\DTO\Product;

use App\Enums\Measure;

/**
 * Product DTO - represents a catalog product
 */
readonly class ProductData
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public int $categoryId,
        public ?int $brandId,
        public Measure $defaultMeasure,
        public ?float $packageSize,
        public ?string $packageUnit,
        public ?string $description,
        public ?string $barcode,
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
            'categoryId' => $this->categoryId,
            'brandId' => $this->brandId,
            'defaultMeasure' => $this->defaultMeasure->value,
            'packageSize' => $this->packageSize,
            'packageUnit' => $this->packageUnit,
            'description' => $this->description,
            'barcode' => $this->barcode,
            'isActive' => $this->isActive,
        ];
    }
}
