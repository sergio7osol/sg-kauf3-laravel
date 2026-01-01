<?php

namespace App\DTO;

/**
 * Label DTO - represents a user-defined label for categorizing purchases
 */
readonly class LabelData
{
    public function __construct(
        public int $id,
        public int $userId,
        public string $name,
        public ?string $description,
        public string $createdAt,
    ) {}

    /**
     * Convert to array for JSON serialization
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->userId,
            'name' => $this->name,
            'description' => $this->description,
            'createdAt' => $this->createdAt,
        ];
    }
}
