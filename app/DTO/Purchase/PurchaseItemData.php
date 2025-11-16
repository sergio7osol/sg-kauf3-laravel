<?php

namespace App\DTO\Purchase;

use App\Enums\Measure;

/**
 * Purchase Item DTO - represents a line item in a purchase
 * Maps to the old frontend "Product" interface but with normalized catalog references and VAT tracking
 */
readonly class PurchaseItemData
{
    public function __construct(
        public int $id,
        public int $purchaseId,
        public ?int $productId,
        public ?int $categoryId,
        public ?int $brandId,
        public string $productNameSnapshot,
        public Measure $measure,
        public float $quantity,
        public float $unitPriceNet,
        public float $unitPriceGross,
        public float $lineDiscountAmount,
        public ?float $lineDiscountPercent,
        public float $vatRate,
        public float $vatAmount,
        public float $lineTotalNet,
        public float $lineTotalGross,
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
            'productId' => $this->productId,
            'categoryId' => $this->categoryId,
            'brandId' => $this->brandId,
            'name' => $this->productNameSnapshot,
            'measure' => $this->measure->value,
            'quantity' => $this->quantity,
            'unitPrice' => [
                'net' => $this->unitPriceNet,
                'gross' => $this->unitPriceGross,
            ],
            'discount' => [
                'amount' => $this->lineDiscountAmount,
                'percent' => $this->lineDiscountPercent,
            ],
            'vat' => [
                'rate' => $this->vatRate,
                'amount' => $this->vatAmount,
            ],
            'lineTotal' => [
                'net' => $this->lineTotalNet,
                'gross' => $this->lineTotalGross,
            ],
            'notes' => $this->notes,
        ];
    }

    /**
     * Get legacy format compatible with old frontend Product type
     */
    public function toLegacyFormat(): array
    {
        return [
            'name' => $this->productNameSnapshot,
            'price' => $this->unitPriceGross,
            'weightAmount' => $this->quantity,
            'measure' => $this->measure->value,
            'description' => $this->notes,
            'discount' => $this->lineDiscountPercent 
                ? "{$this->lineDiscountPercent}%" 
                : ($this->lineDiscountAmount > 0 ? $this->lineDiscountAmount : 0),
        ];
    }
}
