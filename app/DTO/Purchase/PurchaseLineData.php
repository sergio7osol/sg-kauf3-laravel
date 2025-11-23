<?php

namespace App\DTO\Purchase;

/**
 * Purchase Line DTO - represents a single line item in a purchase
 */
readonly class PurchaseLineData
{
    public function __construct(
        public int $id,
        public int $purchaseId,
        public int $lineNumber,
        public ?int $productId,
        public string $description,
        public float $quantity,
        public int $unitPrice,
        public int $lineAmount,
        public float $taxRate,
        public int $taxAmount,
        public ?float $discountPercent,
        public ?int $discountAmount,
        public ?string $notes,
    ) {}

    /**
     * Convert to array for JSON serialization
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'purchaseId' => $this->purchaseId,
            'lineNumber' => $this->lineNumber,
            'productId' => $this->productId,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unitPrice' => $this->unitPrice,
            'lineAmount' => $this->lineAmount,
            'taxRate' => $this->taxRate,
            'taxAmount' => $this->taxAmount,
            'discountPercent' => $this->discountPercent,
            'discountAmount' => $this->discountAmount,
            'notes' => $this->notes,
        ];
    }
}
