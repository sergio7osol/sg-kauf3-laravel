<?php

namespace App\DTO\Purchase;

/**
 * Purchase DTO - represents a complete purchase transaction
 * Maps to the old frontend "BuyInfo" interface but with normalized references
 */
readonly class PurchaseData
{
    /**
     * @param PurchaseItemData[] $items
     */
    public function __construct(
        public int $id,
        public string $purchaseDate,
        public ?string $purchaseTime,
        public int $currencyId,
        public string $currencyCode,
        public int $paymentMethodId,
        public string $paymentMethodName,
        public ?int $shopId,
        public ?string $shopName,
        public ?int $shopAddressId,
        public ?string $receiptNumber,
        public float $subtotalNet,
        public float $totalVat,
        public float $totalGross,
        public ?string $vatSummary,
        public ?string $notes,
        public array $items,
    ) {}

    /**
     * Convert to array for JSON serialization
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'purchaseDate' => $this->purchaseDate,
            'purchaseTime' => $this->purchaseTime,
            'currency' => [
                'id' => $this->currencyId,
                'code' => $this->currencyCode,
            ],
            'paymentMethod' => [
                'id' => $this->paymentMethodId,
                'name' => $this->paymentMethodName,
            ],
            'shop' => $this->shopId ? [
                'id' => $this->shopId,
                'name' => $this->shopName,
            ] : null,
            'shopAddressId' => $this->shopAddressId,
            'receiptNumber' => $this->receiptNumber,
            'totals' => [
                'net' => $this->subtotalNet,
                'vat' => $this->totalVat,
                'gross' => $this->totalGross,
            ],
            'vatSummary' => $this->vatSummary,
            'notes' => $this->notes,
            'items' => array_map(fn($item) => $item->toArray(), $this->items),
        ];
    }

    /**
     * Get legacy format compatible with old frontend BuyInfo type
     */
    public function toLegacyFormat(): array
    {
        return [
            'date' => $this->purchaseDate,
            'time' => $this->purchaseTime ?? '00:00',
            'currency' => $this->currencyCode,
            'payMethod' => $this->paymentMethodName,
            'shopName' => $this->shopName ?? '',
            'products' => array_map(fn($item) => $item->toLegacyFormat(), $this->items),
        ];
    }
}
