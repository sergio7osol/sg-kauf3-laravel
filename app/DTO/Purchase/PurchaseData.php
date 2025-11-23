<?php

namespace App\DTO\Purchase;

/**
 * Purchase DTO - represents a complete purchase transaction
 */
readonly class PurchaseData
{
    /**
     * @param PurchaseLineData[] $lines
     */
    public function __construct(
        public int $id,
        public int $userId,
        public int $shopId,
        public int $shopAddressId,
        public string $purchaseDate,
        public string $currency,
        public string $status,
        public int $subtotal,
        public int $taxAmount,
        public int $totalAmount,
        public ?string $notes,
        public ?string $receiptNumber,
        public array $lines = [],
    ) {}

    /**
     * Convert to array for JSON serialization
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->userId,
            'shopId' => $this->shopId,
            'shopAddressId' => $this->shopAddressId,
            'purchaseDate' => $this->purchaseDate,
            'currency' => $this->currency,
            'status' => $this->status,
            'subtotal' => $this->subtotal,
            'taxAmount' => $this->taxAmount,
            'totalAmount' => $this->totalAmount,
            'notes' => $this->notes,
            'receiptNumber' => $this->receiptNumber,
            'lines' => array_map(fn($line) => $line->toArray(), $this->lines),
        ];
    }
}
