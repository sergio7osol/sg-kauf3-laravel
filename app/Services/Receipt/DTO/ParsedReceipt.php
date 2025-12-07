<?php

namespace App\Services\Receipt\DTO;

/**
 * Fully parsed receipt ready for form pre-fill.
 */
readonly class ParsedReceipt
{
    /**
     * @param ParsedLineItem[] $items
     * @param string[] $warnings
     */
    public function __construct(
        public bool $success,
        public ?string $shopName,
        public ?int $shopId,
        public ?string $addressDisplay,
        public ?int $addressId,
        public ?string $date,          // YYYY-MM-DD format
        public ?string $time,          // HH:MM format
        public string $currency,
        public array $items,
        public float $subtotal,
        public float $total,
        public array $warnings = [],
        public ?string $error = null,
        public string $confidence = 'medium',
    ) {}

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'shopName' => $this->shopName,
            'shopId' => $this->shopId,
            'addressDisplay' => $this->addressDisplay,
            'addressId' => $this->addressId,
            'date' => $this->date,
            'time' => $this->time,
            'currency' => $this->currency,
            'items' => array_map(fn(ParsedLineItem $item) => $item->toArray(), $this->items),
            'subtotal' => $this->subtotal,
            'total' => $this->total,
            'warnings' => $this->warnings,
            'error' => $this->error,
            'confidence' => $this->confidence,
        ];
    }

    public static function failure(string $error): self
    {
        return new self(
            success: false,
            shopName: null,
            shopId: null,
            addressDisplay: null,
            addressId: null,
            date: null,
            time: null,
            currency: 'EUR',
            items: [],
            subtotal: 0,
            total: 0,
            warnings: [],
            error: $error,
            confidence: 'low',
        );
    }
}
