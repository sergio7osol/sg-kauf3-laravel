<?php

namespace App\Services\Receipt\Parsers;

use App\Services\Receipt\DTO\ParsedLineItem;
use App\Services\Receipt\DTO\ParsedReceipt;
use App\Services\Receipt\ReceiptParserInterface;

/**
 * Parser for DM Drogerie receipts (German format).
 */
class DmReceiptParser implements ReceiptParserInterface
{
    public function canParse(string $text): bool
    {
        // Look for DM identifiers
        return (bool) preg_match('/\b(dm-drogerie|dm\.de|DM-Rabatte|dm-Rabatte)\b/i', $text);
    }

    public function getShopName(): string
    {
        return 'DM';
    }

    public function parse(string $text, ?callable $debug = null): ParsedReceipt
    {
        // Note: debug callback not yet implemented for DM parser
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

        // Extract address (DM receipts don't always have address in text)
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

        // Calculate subtotal
        $subtotal = $this->calculateSubtotal($items);

        // Payment method is never on receipt
        $warnings[] = 'Payment method not detected - please select manually';

        return new ParsedReceipt(
            success: true,
            shopName: 'DM',
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

    private function extractDate(string $text): ?string
    {
        // DM format: DD.MM.YYYY at top
        if (preg_match('/(\d{2})\.(\d{2})\.(\d{4})\b/', $text, $matches)) {
            return "{$matches[3]}-{$matches[2]}-{$matches[1]}";
        }

        // Fallback: DD.MM.YY
        if (preg_match('/(\d{2})\.(\d{2})\.(\d{2})\b/', $text, $matches)) {
            return "20{$matches[3]}-{$matches[2]}-{$matches[1]}";
        }

        return null;
    }

    private function extractTime(string $text): ?string
    {
        // DM often has time on separate line or after date
        if (preg_match('/\b(\d{2}):(\d{2})\b/', $text, $matches)) {
            return $matches[1] . ':' . $matches[2];
        }

        return null;
    }

    private function extractAddress(string $text): array
    {
        $result = ['display' => null, 'street' => null, 'postalCode' => null, 'city' => null];

        // DM receipts may not always have address in the extracted text
        // Try to find German address pattern
        if (preg_match('/([A-Za-zäöüÄÖÜß\s\-\.]+(?:straße|strasse|str\.|weg|platz|allee|chaussee)\s*\d+[a-z]?)/i', $text, $streetMatch)) {
            $result['street'] = trim($streetMatch[1]);
        }

        if (preg_match('/\b(\d{5})\s+([A-Za-zäöüÄÖÜß\-]+)\b/', $text, $cityMatch)) {
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

        $i = 0;
        while ($i < count($lines)) {
            $line = trim($lines[$i]);

            if (empty($line)) {
                $i++;
                continue;
            }

            // Skip header/footer lines
            if ($this->isHeaderOrFooter($line)) {
                $i++;
                continue;
            }

            // Check for coupon/discount lines
            if (preg_match('/^(Coupon\s+.+|Partner-Rabatte.*)$/i', $line, $couponMatch)) {
                // Next line might have the discount value
                if (isset($lines[$i + 1]) && preg_match('/^-(\d+[,\.]\d{2})/', trim($lines[$i + 1]), $valueMatch)) {
                    $price = -$this->parsePrice($valueMatch[1]);
                    $items[] = new ParsedLineItem(
                        name: trim($couponMatch[1]),
                        quantity: 1,
                        unit: 'piece',
                        unitPrice: $price,
                        totalPrice: $price,
                        confidence: 'high',
                        isDiscount: true,
                    );
                    $i += 2;
                    continue;
                }
                $i++;
                continue;
            }

            // Check for inline discount: "Coupon 20% lavera -1,00"
            if (preg_match('/(Coupon\s+\d+%\s+\S+)\s+(-\d+[,\.]\d{2})/i', $line, $discountMatch)) {
                $price = $this->parsePrice($discountMatch[2]);
                $items[] = new ParsedLineItem(
                    name: trim($discountMatch[1]),
                    quantity: 1,
                    unit: 'piece',
                    unitPrice: $price,
                    totalPrice: $price,
                    confidence: 'high',
                    isDiscount: true,
                );
                $i++;
                continue;
            }

            // DM format: "Nx Price Product Name" on one line, then "Total Qty" on next
            // Example: "3x 1,55 Prof. 10L Müllb. Biofo" then "4,65 1"
            if (preg_match('/^(\d+)x\s+(\d+[,\.]\d{2})\s+(.+)$/i', $line, $multiMatch)) {
                $quantity = (float) $multiMatch[1];
                $unitPrice = $this->parsePrice($multiMatch[2]);
                $name = trim($multiMatch[3]);

                // Check next line for total
                $totalPrice = $quantity * $unitPrice;
                if (isset($lines[$i + 1]) && preg_match('/^(\d+[,\.]\d{2})\s+\d+\s*$/', trim($lines[$i + 1]), $totalMatch)) {
                    $totalPrice = $this->parsePrice($totalMatch[1]);
                    $i++; // Skip the total line
                }

                $items[] = new ParsedLineItem(
                    name: $name,
                    quantity: $quantity,
                    unit: 'piece',
                    unitPrice: $unitPrice,
                    totalPrice: $totalPrice,
                    confidence: 'medium',
                );
                $i++;
                continue;
            }

            // Single item: "Product Name" followed by "Price Qty"
            // Example: "Prof. Citron. Kerze Basilikum" then "1,95 1"
            if (preg_match('/^[A-Za-zäöüÄÖÜß]/', $line) && !preg_match('/^\d/', $line)) {
                $name = $line;

                // Check next line for price
                if (isset($lines[$i + 1]) && preg_match('/^(\d+[,\.]\d{2})\s+(\d+)\s*$/', trim($lines[$i + 1]), $priceMatch)) {
                    $totalPrice = $this->parsePrice($priceMatch[1]);
                    $quantity = (float) $priceMatch[2];

                    // Skip very short names or metadata-looking lines
                    if (strlen($name) >= 3 && !$this->isHeaderOrFooter($name)) {
                        $items[] = new ParsedLineItem(
                            name: $name,
                            quantity: $quantity,
                            unit: 'piece',
                            unitPrice: $totalPrice / max($quantity, 1),
                            totalPrice: $totalPrice,
                            confidence: 'medium',
                        );
                    }
                    $i += 2;
                    continue;
                }
            }

            // ZEILENSTORNO (line cancellation) - negative item
            if (preg_match('/^ZEILENSTORNO$/i', $line)) {
                // Next lines have the item being cancelled
                $i++;
                continue;
            }

            // Negative price line (refund/cancellation)
            if (preg_match('/^(.+)\s+(-\d+[,\.]\d{2})\s+\d+\s*$/i', $line, $refundMatch)) {
                $price = $this->parsePrice($refundMatch[2]);
                $items[] = new ParsedLineItem(
                    name: trim($refundMatch[1]),
                    quantity: 1,
                    unit: 'piece',
                    unitPrice: $price,
                    totalPrice: $price,
                    confidence: 'medium',
                    isDiscount: true,
                );
                $i++;
                continue;
            }

            $i++;
        }

        return $items;
    }

    private function extractTotal(string $text): ?float
    {
        // DM format: "Zu zahlender Betrag EUR" followed by amount
        if (preg_match('/Zu\s+zahlender\s+Betrag\s+EUR\s*\n?\s*.*?(\d+[,\.]\d{2})/i', $text, $match)) {
            return $this->parsePrice($match[1]);
        }

        // Alternative: "SUMME EUR X,XX"
        if (preg_match('/SUMME\s+EUR\s+(\d+[,\.]\d{2})/i', $text, $match)) {
            return $this->parsePrice($match[1]);
        }

        // Alternative: "VISA EUR" followed by amount
        if (preg_match('/VISA\s+EUR\s+(\d+[,\.]\d{2})/i', $text, $match)) {
            return $this->parsePrice($match[1]);
        }

        return null;
    }

    private function parsePrice(string $price): float
    {
        $price = str_replace('.', '', $price);
        $price = str_replace(',', '.', $price);
        return (float) $price;
    }

    private function calculateTotalFromItems(array $items): float
    {
        return array_reduce($items, fn($sum, ParsedLineItem $item) => $sum + $item->totalPrice, 0);
    }

    private function calculateSubtotal(array $items): float
    {
        return array_reduce(
            $items,
            fn($sum, ParsedLineItem $item) => $sum + ($item->isDiscount ? 0 : $item->totalPrice),
            0
        );
    }

    private function isHeaderOrFooter(string $line): bool
    {
        $skipPatterns = [
            '/^\d{2}\.\d{2}\.\d{4}$/', // Date only
            '/^\d{2}:\d{2}$/', // Time only
            '/^D\d+K\/\d+$/', // Receipt code
            '/^\d+\/\d+$/', // Some ID
            '/^\d{4}$/', // 4-digit code
            '/^EUR$/',
            '/^Zwischensumme/i',
            '/^dm-Rabatte/i',
            '/^Partner-Rabatte/i',
            '/^SUMME\s+EUR/i',
            '/^MwSt/i',
            '/^Brutto$/i',
            '/^Netto$/i',
            '/^\d+=\d+.*%/i', // VAT line
            '/^PAYBACK/i',
            '/^Punktestand/i',
            '/^Basis-Punkte/i',
            '/^Öffnungszeiten/i',
            '/^Steuer-Nr/i',
            '/^FISKALINFORMATIONEN/i',
            '/^Start:/i',
            '/^Ende:/i',
            '/^SN-Kasse/i',
            '/^TA-Nummer/i',
            '/^SN-TSE/i',
            '/^Signaturzähler/i',
            '/^Signatur:/i',
            '/^Prüfwert/i',
            '/^K-U-N-D-E/i',
            '/^Terminal-ID/i',
            '/^Kartenzahlung/i',
            '/^Visa\s+kontaktlos/i',
            '/^DKB/i',
            '/^PAN$/i',
            '/^Karte\s+\d/i',
            '/^gültig\s+bis/i',
            '/^EMV-AID/i',
            '/^VU-Nr/i',
            '/^Genehmigungs/i',
            '/^Datum\s+\d/i',
            '/^\*\*\*/i',
            '/^AS-Proc/i',
            '/^Capt/i',
            '/^AS-RC/i',
            '/^APPROVED/i',
            '/^BITTE\s+BELEG/i',
            '/^={5,}/',
            '/^#{5,}/',
        ];

        foreach ($skipPatterns as $pattern) {
            if (preg_match($pattern, $line)) {
                return true;
            }
        }

        return false;
    }
}
