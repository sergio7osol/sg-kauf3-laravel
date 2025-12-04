<?php

namespace App\DTO\Purchase;

use App\DTO\Shop\ShopData;
use App\DTO\Shop\ShopAddressData;
use App\DTO\UserPaymentMethodData;

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
        public ?int $userPaymentMethodId,
        public string $purchaseDate,
        public string $currency,
        public string $status,
        public int $subtotal,
        public int $taxAmount,
        public int $totalAmount,
        public ?string $notes,
        public ?string $receiptNumber,
        public array $lines = [],
        public ?ShopData $shop = null,
        public ?ShopAddressData $shopAddress = null,
        public ?UserPaymentMethodData $userPaymentMethod = null,
    ) {}

    /**
     * Convert to array for JSON serialization
     */
    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'userId' => $this->userId,
            'shopId' => $this->shopId,
            'shopAddressId' => $this->shopAddressId,
            'userPaymentMethodId' => $this->userPaymentMethodId,
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

        // Include shop relation if loaded
        if ($this->shop !== null) {
            $data['shop'] = $this->shop->toArray();
        }

        // Include shopAddress relation if loaded
        if ($this->shopAddress !== null) {
            $data['shopAddress'] = $this->shopAddress->toArray();
        }

        // Include userPaymentMethod relation if loaded
        if ($this->userPaymentMethod !== null) {
            $data['userPaymentMethod'] = $this->userPaymentMethod->toArray();
        }

        return $data;
    }
}
