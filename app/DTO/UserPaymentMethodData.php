<?php

namespace App\DTO;

/**
 * UserPaymentMethod DTO - represents a user-defined payment method
 */
readonly class UserPaymentMethodData
{
    public function __construct(
        public int $id,
        public int $userId,
        public string $name,
        public ?string $notes,
        public bool $isActive,
        public ?int $paymentMethodId,
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
            'notes' => $this->notes,
            'isActive' => $this->isActive,
            'paymentMethodId' => $this->paymentMethodId,
        ];
    }
}
