<?php

namespace App\Services\Receipt\Parsers;

use App\Services\Receipt\DTO\ParsedLineItem;
use App\Services\Receipt\DTO\ParsedReceipt;
use App\Services\Receipt\ReceiptParserInterface;

/**
 * Parser for Decathlon receipts/invoices (German format).
 *
 * Decathlon provides structured invoices (Rechnung) with:
 * - Clear tabular format with columns: Bezeichnung, Artikel-Nr., Menge, Einzelpreis, MwSt., MwStBetrag, Gesamt
 * - Each item has RFID tag in description
 * - Multi-page invoices (1/2, 2/2, etc.)
 * - Net prices + VAT breakdown
 */
class DecathlonReceiptParser implements ReceiptParserInterface
{
    /** @var callable|null */
    private mixed $debug = null;

    public function canParse(string $text): bool
    {
        // Normalize text once for flexible matching (removes whitespace/newlines)
        $normalized = preg_replace('/\s+/u', '', mb_strtoupper($text));

        return
            // Straightforward match (works for most invoices)
            str_contains($normalized, 'DECATHLON')
            // Some PDF exports break the word after "DECAT" and immediately print "Deutschland SE & Co."
            || str_contains($normalized, 'DECATDEUTSCHLANDSE&CO')
            // OCR variants sometimes insert spaces between letters
            || (bool) preg_match('/D\s*E\s*C\s*A\s*T\s*H\s*L\s*O\s*N/u', $text)
            // Fragmented 'DECAT' header text found in some PDF exports (fallback)
            || (bool) preg_match('/D\s*E\s*C\s*A\s*T/i', $text);
    }

    public function getShopName(): string
    {
        return 'Decathlon';
    }

    public function parse(string $text, ?callable $debug = null): ParsedReceipt
    {
        $this->debug = $debug;
        $warnings = [];

        // Extract date
        $date = $this->extractDate($text);
        if (!$date) {
            $warnings[] = 'Could not extract purchase date';
        }

        // Extract time
        $time = $this->extractTime($text);
        if (!$time) {
            $warnings[] = 'Could not extract purchase time';
        }

        // Extract address
        $addressInfo = $this->extractAddress($text);

        // Extract receipt number
        $receiptNumber = $this->extractReceiptNumber($text);
        if ($receiptNumber) {
            $this->log('receipt_number', ['number' => $receiptNumber]);
        }

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

        // Calculate subtotal from items
        $subtotal = $this->calculateSubtotal($items);

        // Payment method note
        $warnings[] = 'Payment method not detected - please select manually';

        return new ParsedReceipt(
            success: true,
            shopName: 'Decathlon',
            shopId: null,
            addressDisplay: $addressInfo['display'] ?? null,
            addressId: null,
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

    private function log(string $event, array $data = []): void
    {
        if ($this->debug) {
            ($this->debug)($event, $data);
        }
    }

    private function extractDate(string $text): ?string
    {
        // Look for "Rechnungsdatum" followed by date DD.MM.YYYY
        if (preg_match('/Rechnungsdatum\s+(\d{2})\.(\d{2})\.(\d{4})/i', $text, $matches)) {
            $day = $matches[1];
            $month = $matches[2];
            $year = $matches[3];
            return "{$year}-{$month}-{$day}";
        }

        // Fallback: look for date in footer "Kasse: X DD/MM/YYYY" or "DD.MM.YYYY"
        if (preg_match('/(\d{2})\.(\d{2})\.(\d{4})/', $text, $matches)) {
            $day = $matches[1];
            $month = $matches[2];
            $year = $matches[3];
            return "{$year}-{$month}-{$day}";
        }

        return null;
    }

    private function extractTime(string $text): ?string
    {
        // Look for time in Kasse line (some Decathlon texts include it, and pdftotext can insert spaces).
        // Examples:
        // - "Kasse: 5 08/10/2022 19:41:56"
        // - "Kasse: 5 08/10/2022 19:41 :56"
        if (preg_match(
            '/Kasse[:\s]+\d+\s+\d{2}[\/\.]\d{2}[\/\.]\d{2,4}\s+(\d{1,2})\s*:\s*(\d{2})(?:\s*:\s*(\d{2}))?/u',
            $text,
            $matches
        )) {
            $hour = (int) $matches[1];
            if ($hour >= 0 && $hour <= 23) {
                return str_pad((string) $hour, 2, '0', STR_PAD_LEFT) . ':' . $matches[2];
            }
        }

        // Fallback: standalone HH:MM:SS (with optional whitespace around colons)
        if (preg_match('/\b(\d{1,2})\s*:\s*(\d{2})\s*:\s*(\d{2})\b/u', $text, $matches)) {
            $hour = (int) $matches[1];
            if ($hour >= 0 && $hour <= 23) {
                return str_pad((string) $hour, 2, '0', STR_PAD_LEFT) . ':' . $matches[2];
            }
        }

        // Fallback: standalone HH:MM (with optional whitespace around colon)
        if (preg_match('/\b(\d{1,2})\s*:\s*(\d{2})\b/u', $text, $matches)) {
            $hour = (int) $matches[1];
            if ($hour >= 0 && $hour <= 23) {
                return str_pad((string) $hour, 2, '0', STR_PAD_LEFT) . ':' . $matches[2];
            }
        }

        return null;
    }

    private function extractAddress(string $text): array
    {
        $result = ['display' => null, 'street' => null, 'postalCode' => null, 'city' => null];

        // IMPORTANT: invoices contain many numeric identifiers (Kundenkarte, etc.).
        // Only extract the shop address from the Decathlon header block.
        $lines = preg_split('/\r?\n/', $text);
        $headerStart = null;
        $headerEnd = null;

        foreach ($lines as $idx => $line) {
            if ($headerStart === null && (preg_match('/^DECATHLON\b/i', trim($line)) || preg_match('/^DECAT\b/i', trim($line)))) {
                $headerStart = $idx;
                continue;
            }
            if ($headerStart !== null && preg_match('/^RECHNUNGSADRESSE\b/i', trim($line))) {
                $headerEnd = $idx;
                break;
            }
        }

        if ($headerStart !== null) {
            $headerBlock = array_slice($lines, $headerStart, ($headerEnd ?? ($headerStart + 12)) - $headerStart);
            $this->log('shop_location', ['header_block_first_lines' => array_slice($headerBlock, 0, 6)]);

            // Find the street line and postal/city line inside this block.
            foreach ($headerBlock as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                // Street: "41-43 Krohnstieg" => normalize to "Krohnstieg 41-43"
                if ($result['street'] === null) {
                    if (preg_match('/^(\d+(?:-\d+)?)\s+([A-Za-zäöüÄÖÜß][A-Za-zäöüÄÖÜß\-\s]+)$/u', $line, $m)) {
                        $result['street'] = $m[2] . ' ' . $m[1];
                        continue;
                    }
                    // Street: "Krohnstieg 41-43" (standard format)
                    if (preg_match('/^([A-Za-zäöüÄÖÜß][A-Za-zäöüÄÖÜß\-\s]+)\s+(\d+(?:-\d+)?)$/u', $line, $m)) {
                        $result['street'] = $m[1] . ' ' . $m[2];
                        continue;
                    }
                    // Street: "Krohnstieg" (without number)
                    if (preg_match('/^([A-Za-zäöüÄÖÜß][A-Za-zäöüÄÖÜß\-\s]+)$/u', $line, $m)) {
                        $result['street'] = $m[1];
                        continue;
                    }
                }

                // Postal/city: "22415 Hamburg"
                if ($result['postalCode'] === null && preg_match('/^(\d{5})\s+([A-Za-zäöüÄÖÜß\-]+)$/u', $line, $m)) {
                    $result['postalCode'] = $m[1];
                    $result['city'] = $m[2];
                    continue;
                }
            }
        }

        // Build display string
        if ($result['street'] || $result['postalCode']) {
            $parts = array_filter([
                $result['street'],
                trim(($result['postalCode'] ?? '') . ' ' . ($result['city'] ?? '')),
            ]);
            $result['display'] = implode(', ', $parts);
        }

        $this->log('address_extracted', $result);

        return $result;
    }

    private function extractReceiptNumber(string $text): ?string
    {
        // Look for "Rechnungsnummer" followed by the number
        // Format: "1 22 0007 0004012438"
        if (preg_match('/Rechnungsnummer\s+([\d\s]+)/i', $text, $matches)) {
            return trim(preg_replace('/\s+/', '', $matches[1])); // Remove spaces
        }

        // Fallback: Transaction number
        if (preg_match('/Transaktionsnummer\s+(\d+)/i', $text, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function extractLineItems(string $text): array
    {
        $items = [];
        $lines = preg_split('/\r?\n/', $text);
        $lineCount = count($lines);

        $this->log('line_extraction_start', ['total_lines' => $lineCount]);

        // Find all lines containing RFID (these are product description lines)
        for ($i = 0; $i < $lineCount; $i++) {
            $line = trim($lines[$i]);

            // Skip empty lines
            if (empty($line)) {
                continue;
            }

            // Pattern: "SHOES... RFID: 962121" (tolerant of OCR errors like "RFID :", "RFIO", "fifID", etc.)
            // Matches "RFID" or similar, optionally followed by non-digits (like "- r" or " :"), then digits.
            if (preg_match('/^(.+?)\s+(?:RFID|RFIO|fifID|RFiTI)[\s\:\-\'\$a-z]*(\d+)/i', $line, $matches)) {
                $productName = trim($matches[1]);
                $rfid = $matches[2];

                $this->log('product_found', ['name' => $productName, 'rfid' => $rfid, 'line' => $i]);

                // Now look for the numeric values in subsequent lines
                // Expected: article_nr, qty, net_price, vat%, vat_amount, gross_total
                $itemData = $this->extractItemValues($lines, $i + 1);

                if ($itemData) {
                    $quantity = $itemData['quantity'] > 0 ? $itemData['quantity'] : 1;
                    // On the invoice, "Gesamt" is the line total for the quantity.
                    $lineTotal = $itemData['grossTotal'];
                    $unitPrice = $lineTotal / $quantity;

                    $items[] = new ParsedLineItem(
                        name: $productName,
                        quantity: $quantity,
                        unit: 'piece',
                        unitPrice: $unitPrice,
                        totalPrice: $lineTotal,
                        confidence: 'high',
                    );

                    $this->log('parsed_item', [
                        'name' => $productName,
                        'quantity' => $itemData['quantity'],
                        'gross_price' => $itemData['grossTotal'],
                        'net_price' => $itemData['netPrice'] ?? null,
                        'vat_rate' => $itemData['vatRate'] ?? null,
                    ]);
                } else {
                    // Fallback: try to find price on the same line or nearby
                    $price = $this->findPriceNearby($lines, $i);
                    if ($price !== null) {
                        $items[] = new ParsedLineItem(
                            name: $productName,
                            quantity: 1,
                            unit: 'piece',
                            unitPrice: $price,
                            totalPrice: $price,
                            confidence: 'medium',
                            warning: 'Price extracted with reduced confidence',
                        );
                    }
                }
            }
        }

        $this->log('line_extraction_complete', ['items_found' => count($items)]);

        return $items;
    }

    /**
     * Extract item values from lines following a product description.
     * Decathlon invoices have columns that pdftotext may linearize as separate lines.
     */
    private function extractItemValues(array $lines, int $startIndex): ?array
    {
        $values = [];
        $lineCount = count($lines);

        // Collect numeric values from subsequent lines
        // NOTE: pdftotext often inserts many blank lines; keep a larger window.
        for ($i = $startIndex; $i < min($startIndex + 40, $lineCount); $i++) {
            $line = trim($lines[$i]);

            // Stop if we hit another product line or section header
            if (preg_match('/RFID:/i', $line) || preg_match('/^(Zwischensumme|Summe|Rechnungsbetrag|RECHNUNG)/i', $line)) {
                break;
            }

            // Skip empty lines
            if (empty($line)) {
                continue;
            }

            // Match article number (7-digit number at start of line)
            if (preg_match('/^(\d{7})$/', $line, $m)) {
                $values['articleNumber'] = $m[1];
                continue;
            }

            // Match quantity (usually "1" alone on a line)
            if (preg_match('/^(\d+)$/', $line, $m) && !isset($values['quantity'])) {
                $values['quantity'] = (int) $m[1];
                continue;
            }

            // Match VAT percentage (e.g., "19%")
            if (preg_match('/^(\d+)%$/', $line, $m)) {
                $values['vatRate'] = (int) $m[1];
                continue;
            }

            // Match price values (e.g., "42.01", "7.98", "49.99")
            if (preg_match('/^(\d+)[.,](\d{2})$/', $line, $m)) {
                $price = (float) ($m[1] . '.' . $m[2]);

                // Assign prices in order: netPrice, vatAmount, grossTotal
                if (!isset($values['netPrice'])) {
                    $values['netPrice'] = $price;
                } elseif (!isset($values['vatAmount'])) {
                    $values['vatAmount'] = $price;
                } elseif (!isset($values['grossTotal'])) {
                    $values['grossTotal'] = $price;
                }
                continue;
            }

            // Match prices that include currency (e.g., "243.89 €")
            if (preg_match('/^(\d+)[.,](\d{2})\s*€$/u', $line, $m)) {
                $price = (float) ($m[1] . '.' . $m[2]);
                if (!isset($values['grossTotal'])) {
                    // In case pdftotext includes the currency only on the last column
                    $values['grossTotal'] = $price;
                }
                continue;
            }
        }

        // Validate we have at least quantity and gross total
        if (isset($values['grossTotal'])) {
            return [
                'articleNumber' => $values['articleNumber'] ?? null,
                'quantity' => $values['quantity'] ?? 1,
                'netPrice' => $values['netPrice'] ?? null,
                'vatRate' => $values['vatRate'] ?? 19,
                'vatAmount' => $values['vatAmount'] ?? null,
                'grossTotal' => $values['grossTotal'],
            ];
        }

        return null;
    }

    /**
     * Fallback: find a price near the product line.
     */
    private function findPriceNearby(array $lines, int $productLineIndex): ?float
    {
        $lineCount = count($lines);

        // Check next few lines for a price
        for ($i = $productLineIndex + 1; $i < min($productLineIndex + 8, $lineCount); $i++) {
            $line = trim($lines[$i]);

            // Match price with € symbol
            if (preg_match('/(\d+)[.,](\d{2})\s*€/', $line, $m)) {
                return (float) ($m[1] . '.' . $m[2]);
            }

            // Match standalone price
            if (preg_match('/^(\d+)[.,](\d{2})$/', $line, $m)) {
                $price = (float) ($m[1] . '.' . $m[2]);
                if ($price > 0 && $price < 10000) {
                    return $price;
                }
            }
        }

        return null;
    }

    private function extractTotal(string $text): ?float
    {
        // Primary: "Rechnungsbetrag" line
        if (preg_match('/Rechnungsbetrag\s+(\d+)[.,](\d{2})\s*€?/i', $text, $matches)) {
            return (float) ($matches[1] . '.' . $matches[2]);
        }

        // Fallback: "Gesamt" line with amount
        if (preg_match('/Gesamt\s+(\d+)[.,](\d{2})\s*€/i', $text, $matches)) {
            return (float) ($matches[1] . '.' . $matches[2]);
        }

        return null;
    }

    private function calculateSubtotal(array $items): float
    {
        $subtotal = 0;
        foreach ($items as $item) {
            if (!$item->isDiscount) {
                $subtotal += $item->totalPrice;
            }
        }
        return $subtotal;
    }

    private function calculateTotalFromItems(array $items): float
    {
        $total = 0;
        foreach ($items as $item) {
            $total += $item->totalPrice;
        }
        return $total;
    }
}
