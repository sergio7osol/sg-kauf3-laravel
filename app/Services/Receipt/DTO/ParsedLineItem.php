<?php

namespace App\Services\Receipt\DTO;

/**
 * A single parsed line item from a receipt.
 */
readonly class ParsedLineItem
{
    public function __construct(
        public string $name,
        public float $quantity,
        public string $unit,           // 'piece', 'kg', 'g', 'l', etc.
        public float $unitPrice,       // In original currency (e.g., EUR)
        public float $totalPrice,      // quantity * unitPrice (or explicit from receipt)
        public string $confidence,     // 'high', 'medium', 'low'
        public ?string $warning = null,
        public bool $isDiscount = false,
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'unitPrice' => $this->unitPrice,
            'totalPrice' => $this->totalPrice,
            'confidence' => $this->confidence,
            'warning' => $this->warning,
            'isDiscount' => $this->isDiscount,
        ];
    }
}
