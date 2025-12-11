<?php

namespace App\Services\Receipt\Parsers;

use App\Services\Receipt\DTO\ParsedLineItem;
use App\Services\Receipt\DTO\ParsedReceipt;
use App\Services\Receipt\ReceiptParserInterface;

/**
 * Parser for Lidl receipts (German format).
 */
class LidlReceiptParser implements ReceiptParserInterface
{
    public function canParse(string $text): bool
    {
        // Look for Lidl identifiers
        return (bool) preg_match('/\b(LIDL|Lidl|lidl)\b/i', $text);
    }

    public function getShopName(): string
    {
        return 'Lidl';
    }

    /** @var callable|null */
    private mixed $debug = null;

    public function parse(string $text, ?callable $debug = null): ParsedReceipt
    {
        $this->debug = $debug;
        $warnings = [];
        $items = [];

        // Extract date and time
        $date = $this->extractDate($text);
        $time = $this->extractTime($text);

        if (!$date) {
             $warnings[] = 'Could not extract purchase date';
        }
        if (!$time) {
            $warnings[] = 'Could not extract purchase time';
        }

        // Extract address
        $addressInfo = $this->extractAddress($text);

        // Extract line items
        $items = $this->extractLineItems($text);

        if (empty($items)) {
            $warnings[] = 'No line items could be extracted';
        }

        // Extract total
        $total = $this->extractTotal($text);

        if ($total === null) {
            $warnings[] = 'Could not extract total amount';
            $total = $this->calculateTotalFromItems($items);
        }

        // Calculate subtotal from items (excluding discounts)
        $subtotal = $this->calculateSubtotal($items);

        // Payment method is never on receipt
        $warnings[] = 'Payment method not detected - please select manually';

        return new ParsedReceipt(
            success: true,
            shopName: 'Lidl',
            shopId: null, // Will be matched by ReceiptImportService
            addressDisplay: $addressInfo['display'] ?? null,
            addressId: null, // Will be matched by ReceiptImportService
            date: $date,
            time: $time,
            currency: 'EUR',
            items: $items,
            subtotal: $subtotal,
            total: $total ?? 0,
            warnings: $warnings,
            confidence: count($warnings) <= 2 ? 'high' : 'medium',
        );
    }

    private function extractDate(string $text): ?string
    {
        // Pattern: DD.MM.YY or DD.MM.YYYY
        if (preg_match('/(\d{2})\.(\d{2})\.(\d{2,4})\b/', $text, $matches)) {
            $day = $matches[1];
            $month = $matches[2];
            $year = $matches[3];

            // Handle 2-digit year
            if (strlen($year) === 2) {
                $year = '20' . $year;
            }

            return "{$year}-{$month}-{$day}";
        }

        return null;
    }

    private function extractTime(string $text): ?string
    {
        // Look for time in the receipt footer pattern: "XXXX XXXXXX/XX DD.MM.YY HH:MM"
        // This is more reliable than generic HH:MM which can match TSE timestamps
        if (preg_match('/\d{4}\s+\d+\/\d+\s+\d{2}\.\d{2}\.\d{2}\s+(\d{2}):(\d{2})/', $text, $matches)) {
            return $matches[1] . ':' . $matches[2];
        }

        // Fallback: look for time after date pattern (DD.MM.YY HH:MM)
        if (preg_match('/\d{2}\.\d{2}\.\d{2}\s+(\d{2}):(\d{2})/', $text, $matches)) {
            // Validate it's a reasonable hour (00-23)
            $hour = (int) $matches[1];
            if ($hour >= 0 && $hour <= 23) {
                return $matches[1] . ':' . $matches[2];
            }
        }

        return null;
    }

    private function extractAddress(string $text): array
    {
        $result = ['display' => null, 'street' => null, 'postalCode' => null, 'city' => null];

        // Lidl receipts have the address near the top (within first ~20 lines)
        // Limit search to header area to avoid matching addresses from coupons/ads
        $lines = explode("\n", $text);
        $headerText = implode("\n", array_slice($lines, 0, 20));

        // Look for German address pattern: Street + number, then postal code + city
        // Example: "Kieler Straße 595" followed by "22525 Hamburg"
        // Includes common German street suffixes: straße, weg, platz, allee, chaussee, damm, ring, ufer, etc.
        $streetPattern = '/([A-Za-zäöüÄÖÜß\s\-\.]+(?:straße|strasse|str\.|weg|platz|allee|chaussee|damm|ring|ufer|steig|gasse|pfad|promenade|kamp|stieg|bogen|hof|markt)\s*\d+[a-z]?)/i';
        
        if (preg_match($streetPattern, $headerText, $streetMatch)) {
            $result['street'] = trim($streetMatch[1]);
        }

        if (preg_match('/\b(\d{5})\s+([A-Za-zäöüÄÖÜß\-]+)\b/', $headerText, $cityMatch)) {
            $result['postalCode'] = $cityMatch[1];
            $result['city'] = $cityMatch[2];
        }

        if ($result['street'] && $result['postalCode'] && $result['city']) {
            $result['display'] = "{$result['street']}, {$result['postalCode']} {$result['city']}";
        }

        return $result;
    }

    private function extractLineItems(string $text): array
    {
        $items = [];
        $lines = explode("\n", $text);

        foreach ($lines as $lineNum => $line) {
            $line = trim($line);

            if (empty($line)) {
                continue;
            }

            // Skip header/footer lines
            if ($this->isHeaderOrFooter($line)) {
                $this->log('skipped_header', ['lineNum' => $lineNum, 'line' => $line]);
                continue;
            }

            // Skip detail lines (quantity breakdowns like "1,550 kg x 1,69 EUR/kg")
            if ($this->isDetailLine($line)) {
                $this->log('skipped_detail', ['lineNum' => $lineNum, 'line' => $line]);
                continue;
            }

            // Check for discount lines: "Lidl Plus Rabatt -X,XX" or "Preisvorteil -X,XX"
            // Note: OCR may produce Unicode minus (−) which regex doesn't catch, so we force negative
            if (preg_match('/(Lidl Plus Rabatt|Preisvorteil|Rabatt)\s+[-−]?(\d+[,\.]\d{2})/i', $line, $discountMatch)) {
                $price = -abs($this->parsePrice($discountMatch[2])); // Always negative for discounts
                $this->log('parsed_discount', ['lineNum' => $lineNum, 'line' => $line, 'name' => trim($discountMatch[1]), 'price' => $price]);
                $items[] = new ParsedLineItem(
                    name: trim($discountMatch[1]),
                    quantity: 1,
                    unit: 'piece',
                    unitPrice: $price,
                    totalPrice: $price,
                    confidence: 'high',
                    isDiscount: true,
                );
                continue;
            }

            // Check for Pfandrückgabe (deposit return)
            if (preg_match('/Pfandr[üu]ckgabe\s+(-?\d+[,\.]\d{2})/i', $line, $depositMatch)) {
                $price = $this->parsePrice($depositMatch[1]);
                $items[] = new ParsedLineItem(
                    name: 'Pfandrückgabe',
                    quantity: 1,
                    unit: 'piece',
                    unitPrice: $price,
                    totalPrice: $price,
                    confidence: 'high',
                    isDiscount: true,
                );
                continue;
            }

            // Check for Pfand return shorthand: "-3 X 0,25" (returning 3 bottles at 0.25 each)
            if (preg_match('/^[-−](\d+)\s*[Xx]\s*(\d+[,\.]\d{2})/i', $line, $pfandReturnMatch)) {
                $quantity = (int) $pfandReturnMatch[1];
                $unitPrice = $this->parsePrice($pfandReturnMatch[2]);
                $totalPrice = -($quantity * $unitPrice); // Negative because it's a return/credit
                $this->log('parsed_pfand_return', ['lineNum' => $lineNum, 'line' => $line, 'qty' => $quantity, 'total' => $totalPrice]);
                $items[] = new ParsedLineItem(
                    name: 'Pfand Rückgabe',
                    quantity: $quantity,
                    unit: 'piece',
                    unitPrice: -$unitPrice,
                    totalPrice: $totalPrice,
                    confidence: 'high',
                    isDiscount: true,
                );
                continue;
            }

            // Regular item patterns:
            // "Product name X,XX A" (single item)
            // "Product name X,XX x N Y,YY A" (multiple items)
            // "N x X,XX Product name Y,YY A"
            if (preg_match('/^(.+?)\s+(\d+[,\.]\d{2})\s*(?:x\s*(\d+))?\s*(\d+[,\.]\d{2})?\s*[AB]?\s*$/i', $line, $itemMatch)) {
                $name = trim($itemMatch[1]);
                $price1 = $this->parsePrice($itemMatch[2]);
                $quantity = isset($itemMatch[3]) && $itemMatch[3] ? (float) $itemMatch[3] : 1;
                $totalPrice = isset($itemMatch[4]) && $itemMatch[4] ? $this->parsePrice($itemMatch[4]) : $price1;

                // Skip if name looks like metadata
                if (strlen($name) < 3 || is_numeric($name)) {
                    continue;
                }

                $this->log('parsed_item', ['lineNum' => $lineNum, 'line' => $line, 'name' => $name, 'qty' => $quantity, 'total' => $totalPrice]);
                $items[] = new ParsedLineItem(
                    name: $name,
                    quantity: $quantity,
                    unit: 'piece',
                    unitPrice: $quantity > 1 ? $price1 : $totalPrice,
                    totalPrice: $totalPrice,
                    confidence: 'medium',
                );
                continue;
            }

            // Pfand (deposit) lines: "Pfand 0,25 EM 0,25 x 6 1,50 B"
            if (preg_match('/Pfand\s+\d+[,\.]\d{2}.*?(\d+[,\.]\d{2})\s*[AB]?\s*$/i', $line, $pfandMatch)) {
                $totalPrice = $this->parsePrice($pfandMatch[1]);
                $this->log('parsed_pfand', ['lineNum' => $lineNum, 'line' => $line, 'total' => $totalPrice]);
                $items[] = new ParsedLineItem(
                    name: 'Pfand',
                    quantity: 1,
                    unit: 'piece',
                    unitPrice: $totalPrice,
                    totalPrice: $totalPrice,
                    confidence: 'medium',
                );
                continue;
            }

            // Line not matched by any pattern
            $this->log('unmatched_line', ['lineNum' => $lineNum, 'line' => $line]);
        }

        return $items;
    }

    private function extractTotal(string $text): ?float
    {
        // Pattern: "zu zahlen XX,XX" or "Summe XX,XX"
        if (preg_match('/zu\s+zahlen\s+(\d+[,\.]\d{2})/i', $text, $match)) {
            return $this->parsePrice($match[1]);
        }

        if (preg_match('/Summe\s+EUR?\s*(\d+[,\.]\d{2})/i', $text, $match)) {
            return $this->parsePrice($match[1]);
        }

        return null;
    }

    private function parsePrice(string $price): float
    {
        // Convert German format (1.234,56) to float
        $price = str_replace('.', '', $price); // Remove thousand separators
        $price = str_replace(',', '.', $price); // Convert decimal separator

        return (float) $price;
    }

    private function calculateTotalFromItems(array $items): float
    {
        return array_reduce($items, fn($sum, ParsedLineItem $item) => $sum + $item->totalPrice, 0);
    }

    private function calculateSubtotal(array $items): float
    {
        // Sum of all item totals (discounts have negative prices, so they reduce the sum)
        return array_reduce(
            $items,
            fn($sum, ParsedLineItem $item) => $sum + $item->totalPrice,
            0
        );
    }

    private function isHeaderOrFooter(string $line): bool
    {
        $skipPatterns = [
            '/^Bonkopie$/i',
            '/^LIDL$/i',
            '/^EUR$/i',
            '/^Kieler/i', // Address line
            '/^\d{5}\s+\w+$/', // Postal code + city
            '/^MWST/i',
            '/^Summe$/i',
            '/^TSE\s+Trans/i',
            '/^Seriennr/i',
            '/^Signatur/i',
            '/^\d{4}\s+\d+\/\d+/', // Receipt number pattern
            '/^UST-ID/i',
            '/^K-U-N-D-E/i',
            '/^Bezahlung/i',
            '/^Betrag/i',
            '/^Terminal/i',
            '/^Kartennr/i',
            '/^Visa\s+kontaktlos/i',
            '/^VU-Nummer/i',
            '/^Autorisierung/i',
            '/^EMV-Daten/i',
            '/^\*\*/i',
            '/^Mit\s+Lidl\s+Plus/i',
            '/gespart$/i',
            '/^VIELEN\s+DANK/i',
            '/^Kostenlose/i',
            '/^www\./i',
            '/^Einkauf\s+get/i',
            '/^Details\s+zur/i',
            '/^Eingel[öo]ste/i',
            '/^Aktion\s+g[üu]ltig/i',
            '/^Coupon\s+nur/i',
            '/^Giltig\s+in/i',
            '/^Pro\s+Einkauf/i',
            '/^Keine\s+Bar/i',
            '/^Coupons\s+zu/i',
            '/^\d+[,\.]\d{2}\s*EUR\s+gespart/i',
            '/^Gesamter\s+Preisvorteil/i',
            // VAT summary lines: "A = 19% MwSt. 12,72 aus 79,70"
            '/^[AB]\s*=?\s*\d+[,\.]?\d*\s*%/i',
            '/\baus\s+\d+[,\.]\d{2}/i',  // Word boundary to avoid matching "kraus", etc.
            // Stray total/price lines
            '/^\d+[,\.]\d{2}\s*EUR\s*$/i',
            '/^EUR\s+\d+[,\.]\d{2}\s*$/i',
            // Start/Ende timestamps
            '/^Start:/i',
            '/^Ende:/i',
            // TSE/signature related
            '/^2\d{3}-\d{2}-\d{2}T/i',  // ISO dates like 2025-12-03T...
            '/^Transaktions/i', 
            '/^Zeitformat/i',
            '/^TA-Nr/i',
            // Payment/total lines that look like items
            '/^zu\s+zahlen/i',
            '/^Kreditkarte/i',
            '/^Maestro/i',
            '/^Visa/i',
            '/^EC-Karte/i',
            '/^Girocard/i',
            '/^Bar\s+\d/i',
            '/^Summe\s+\d/i',
            // Note: Pfand return lines like "-3 X 0,25" should be parsed, not skipped
            // They represent bottle deposit returns (credits)
        ];

        foreach ($skipPatterns as $pattern) {
            if (preg_match($pattern, $line)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if line is a detail/breakdown line (not a product line).
     * Examples: "1,550 kg x 1,69 EUR/kg", "0,25 x 6"
     */
    private function isDetailLine(string $line): bool
    {
        $detailPatterns = [
            // Weight breakdown: "1,550 kg x 1,69 EUR/kg"
            '/^\d+[,\.]\d+\s*(kg|g|l|ml)\s*x\s*\d+[,\.]\d+\s*EUR\/(kg|g|l|ml)/i',
            // Simple quantity breakdown: "0,25 x 6" (no product name)
            '/^\d+[,\.]\d+\s*x\s*\d+$/i',
            // Price per unit lines
            '/^\d+[,\.]\d+\s*EUR\/(kg|g|l|ml|St)/i',
            // Lines that are just numbers (article codes, etc.)
            '/^\d{6,}\s*\d*$/',
        ];

        foreach ($detailPatterns as $pattern) {
            if (preg_match($pattern, $line)) {
                return true;
            }
        }

        return false;
    }

    private function log(string $event, array $context = []): void
    {
        if ($this->debug) {
            ($this->debug)($event, $context);
        }
    }
}
