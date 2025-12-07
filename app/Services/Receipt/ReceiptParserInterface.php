<?php

namespace App\Services\Receipt;

use App\Services\Receipt\DTO\ParsedReceipt;

/**
 * Contract for shop-specific receipt parsers.
 */
interface ReceiptParserInterface
{
    /**
     * Check if this parser can handle the given receipt text.
     */
    public function canParse(string $text): bool;

    /**
     * Get the shop name this parser handles.
     */
    public function getShopName(): string;

    /**
     * Parse raw receipt text into structured data.
     */
    public function parse(string $text, ?callable $debug = null): ParsedReceipt;
}
