<?php

namespace App\DTO\Product;

/**
 * Category DTO - represents a product category with optional hierarchy
 */
readonly class CategoryData
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public ?int $parentId,
        public ?string $description,
        public ?string $icon,
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
            'parentId' => $this->parentId,
            'description' => $this->description,
            'icon' => $this->icon,
            'displayOrder' => $this->displayOrder,
            'isActive' => $this->isActive,
        ];
    }
}
