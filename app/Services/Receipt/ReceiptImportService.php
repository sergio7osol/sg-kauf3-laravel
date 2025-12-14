<?php

namespace App\Services\Receipt;

use App\Models\Shop;
use App\Models\ShopAddress;
use App\Services\Receipt\DTO\ExtractionResult;
use App\Services\Receipt\DTO\ParsedReceipt;
use App\Services\Receipt\Parsers\DecathlonReceiptParser;
use App\Services\Receipt\Parsers\DmReceiptParser;
use App\Services\Receipt\Parsers\LidlReceiptParser;

/**
 * Orchestrates receipt import: extraction → parsing → DB matching.
 */
class ReceiptImportService
{
    /** @var ReceiptParserInterface[] */
    private array $parsers;

    public function __construct(
        private readonly ReceiptTextExtractor $extractor,
    ) {
        // Register available parsers (order matters for detection)
        $this->parsers = [
            new LidlReceiptParser(),
            new DmReceiptParser(),
            new DecathlonReceiptParser(),
        ];
    }

    /**
     * Import a receipt from file path.
     *
     * @param string $filePath Absolute path to PDF/image file
     * @param callable|null $debug Optional debug callback
     * @return ParsedReceipt
     */
    public function importFromFile(string $filePath, ?callable $debug = null): ParsedReceipt
    {
        // Step 1: Extract text
        $extraction = $this->extractor->extract($filePath);

        if (!$extraction->success) {
            return ParsedReceipt::failure("Text extraction failed: {$extraction->error}");
        }

        return $this->parseText($extraction->text, $debug);
    }

    /**
     * Parse already-extracted text (useful for testing).
     *
     * @param callable|null $debug Optional debug callback
     */
    public function parseText(string $text, ?callable $debug = null): ParsedReceipt
    {
        if (empty(trim($text))) {
            return ParsedReceipt::failure('Extracted text is empty');
        }

        // Step 2: Detect shop and select parser
        $parser = $this->detectParser($text);

        if (!$parser) {
            return ParsedReceipt::failure(
                'Could not identify shop from receipt. Supported shops: ' .
                implode(', ', array_map(fn($p) => $p->getShopName(), $this->parsers))
            );
        }

        if ($debug) {
            $debug('parser_detected', ['parser' => $parser->getShopName()]);
        }

        // Step 3: Parse receipt
        $parsed = $parser->parse($text, $debug);

        // Step 4: Match shop/address in DB
        $parsed = $this->matchDatabaseEntities($parsed);

        return $parsed;
    }

    /**
     * Detect which parser can handle this receipt.
     */
    private function detectParser(string $text): ?ReceiptParserInterface
    {
        foreach ($this->parsers as $parser) {
            if ($parser->canParse($text)) {
                return $parser;
            }
        }

        return null;
    }

    /**
     * Match parsed shop/address to database records.
     */
    private function matchDatabaseEntities(ParsedReceipt $parsed): ParsedReceipt
    {
        $warnings = $parsed->warnings;
        $shopId = null;
        $addressId = null;
        $addressDisplay = $parsed->addressDisplay;

        // Try to find shop by name
        if ($parsed->shopName) {
            $shop = Shop::where('name', $parsed->shopName)
                ->orWhere('name', 'LIKE', "%{$parsed->shopName}%")
                ->first();

            if ($shop) {
                $shopId = $shop->id;

                // Try to match address if we have parsed address info
                if ($addressDisplay) {
                    $address = $this->findMatchingAddress($shop->id, $addressDisplay);
                    if ($address) {
                        $addressId = $address->id;
                        $addressDisplay = $this->formatAddressDisplay($address);
                    } else {
                        $warnings[] = "Address not found in database. Detected: {$addressDisplay}";
                    }
                } else {
                    // No address parsed, try to get primary or first address
                    $address = ShopAddress::where('shop_id', $shop->id)
                        ->orderBy('is_primary', 'desc')
                        ->orderBy('id')
                        ->first();

                    if ($address) {
                        $addressId = $address->id;
                        $addressDisplay = $this->formatAddressDisplay($address);
                        $warnings[] = 'Address auto-selected (first available for this shop)';
                    } else {
                        $warnings[] = 'No address found for this shop';
                    }
                }
            } else {
                $warnings[] = "Shop '{$parsed->shopName}' not found in database. Please create it first.";
            }
        }

        // Create new ParsedReceipt with matched IDs
        return new ParsedReceipt(
            success: $parsed->success,
            shopName: $parsed->shopName,
            shopId: $shopId,
            addressDisplay: $addressDisplay,
            addressId: $addressId,
            date: $parsed->date,
            time: $parsed->time,
            currency: $parsed->currency,
            items: $parsed->items,
            subtotal: $parsed->subtotal,
            total: $parsed->total,
            warnings: $warnings,
            error: $parsed->error,
            confidence: $parsed->confidence,
        );
    }

    /**
     * Try to find a matching address based on parsed address string.
     */
    private function findMatchingAddress(int $shopId, string $addressDisplay): ?ShopAddress
    {
        // Extract postal code from display string
        if (preg_match('/\b(\d{5})\b/', $addressDisplay, $postalMatch)) {
            $postalCode = $postalMatch[1];

            $address = ShopAddress::where('shop_id', $shopId)
                ->where('postal_code', $postalCode)
                ->first();

            if ($address) {
                return $address;
            }
        }

        // Extract street name (first significant word)
        if (preg_match('/^([A-Za-zäöüÄÖÜß]+)/i', $addressDisplay, $streetMatch)) {
            $streetWord = $streetMatch[1];

            $address = ShopAddress::where('shop_id', $shopId)
                ->where('street', 'LIKE', "%{$streetWord}%")
                ->first();

            if ($address) {
                return $address;
            }
        }

        return null;
    }

    /**
     * Format address for display.
     */
    private function formatAddressDisplay(ShopAddress $address): string
    {
        $parts = [];

        if ($address->street) {
            $street = $address->street;
            if ($address->house_number) {
                $street .= ' ' . $address->house_number;
            }
            $parts[] = $street;
        }

        if ($address->postal_code || $address->city) {
            $cityPart = trim("{$address->postal_code} {$address->city}");
            if ($cityPart) {
                $parts[] = $cityPart;
            }
        }

        return implode(', ', $parts);
    }

    /**
     * Get list of supported shop names.
     */
    public function getSupportedShops(): array
    {
        return array_map(fn($p) => $p->getShopName(), $this->parsers);
    }
}
