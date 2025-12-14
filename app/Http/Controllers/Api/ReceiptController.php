<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ParseReceiptRequest;
use App\Services\Receipt\ReceiptImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

/**
 * Handles receipt upload and parsing.
 * 
 * Flow:
 * 1. User uploads receipt → parse() returns preview
 * 2. User reviews/edits preview
 * 3. User confirms → POST /api/purchases with final data
 */
class ReceiptController extends Controller
{
    public function __construct(
        private readonly ReceiptImportService $importService,
    ) {}

    /**
     * Parse an uploaded receipt and return structured preview data.
     * Does NOT save to database - that's done via PurchaseController::store().
     */
    public function parse(ParseReceiptRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $includeDebug = $request->boolean('debug', false);

        // Store uploaded file temporarily
        $tempPath = $file->store('receipts/temp', 'local');
        $absolutePath = Storage::disk('local')->path($tempPath);

        try {
            // Collect debug events if requested
            $debugLog = [];
            $debug = $includeDebug
                ? function (string $event, array $context = []) use (&$debugLog) {
                    $debugLog[] = ['event' => $event, 'context' => $context];
                }
                : null;

            // Parse the receipt
            $result = $this->importService->importFromFile($absolutePath, $debug);

            // Build response
            $response = [
                'success' => $result->success,
                'data' => $result->success ? $this->formatPreview($result) : null,
                'warnings' => $result->warnings,
                'error' => $result->error,
                'confidence' => $result->confidence,
                'field_warnings' => $result->success ? $this->computeFieldWarnings($result) : null,
            ];

            if ($includeDebug) {
                $response['debug'] = $this->formatDebug($debugLog);
            }

            return response()->json($response, $result->success ? 200 : 422);

        } finally {
            // Clean up temp file
            if ($tempPath && Storage::disk('local')->exists($tempPath)) {
                Storage::disk('local')->delete($tempPath);
            }
        }
    }

    /**
     * Format parsed receipt for preview response.
     */
    private function formatPreview($result): array
    {
        return [
            'shop' => [
                'name' => $result->shopName,
                'id' => $result->shopId,
            ],
            'address' => [
                'display' => $result->addressDisplay,
                'id' => $result->addressId,
            ],
            'purchase_date' => $result->date,
            'purchase_time' => $result->time,
            'currency' => $result->currency,
            'subtotal' => $result->subtotal,
            'total' => $result->total,
            'items' => array_map(fn($item) => [
                'name' => $item->name,
                'quantity' => $item->quantity,
                'unit' => $item->unit,
                'unit_price' => $item->unitPrice,
                'total_price' => $item->totalPrice,
                'is_discount' => $item->isDiscount,
                'confidence' => $item->confidence,
                'warning' => $item->warning,
                // Submission-ready fields for POST /api/purchases
                // For discounts: unit_price=0, discount_amount=absolute value in cents
                // For regular items: unit_price in cents, discount_amount=0
                'submit_unit_price' => $item->isDiscount
                    ? 0
                    : (int) round(abs($item->unitPrice) * 100),
                'submit_discount_amount' => $item->isDiscount
                    ? (int) round(abs($item->totalPrice) * 100)
                    : 0,
            ], $result->items),
        ];
    }

    /**
     * Format debug log for response.
     */
    private function formatDebug(array $debugLog): array
    {
        $eventCounts = [];
        foreach ($debugLog as $entry) {
            $event = $entry['event'];
            $eventCounts[$event] = ($eventCounts[$event] ?? 0) + 1;
        }

        return [
            'event_summary' => $eventCounts,
            'events' => array_slice($debugLog, 0, 100), // Limit to first 100 events
        ];
    }

    /**
     * Compute per-field confidence and warnings for UI highlighting.
     * Returns field_warnings object with confidence (high|medium|low) and optional warning message.
     */
    private function computeFieldWarnings($result): array
    {
        $fieldWarnings = [];

        // shop_id: high if matched, medium if name detected but not in DB, low if no name
        if ($result->shopId !== null) {
            $fieldWarnings['shop_id'] = ['confidence' => 'high', 'warning' => null];
        } elseif ($result->shopName !== null) {
            $fieldWarnings['shop_id'] = ['confidence' => 'medium', 'warning' => 'Shop detected but not found in database'];
        } else {
            $fieldWarnings['shop_id'] = ['confidence' => 'low', 'warning' => 'Could not detect shop'];
        }

        // shop_address_id: high if matched, medium if address detected but not in DB, low if no address
        if ($result->addressId !== null) {
            $fieldWarnings['shop_address_id'] = ['confidence' => 'high', 'warning' => null];
        } elseif ($result->addressDisplay !== null) {
            $fieldWarnings['shop_address_id'] = ['confidence' => 'medium', 'warning' => 'Address detected but not found in database'];
        } else {
            $fieldWarnings['shop_address_id'] = ['confidence' => 'low', 'warning' => 'Could not detect address'];
        }

        // purchase_date: high if extracted, low if null
        if ($result->date !== null) {
            $fieldWarnings['purchase_date'] = ['confidence' => 'high', 'warning' => null];
        } else {
            $fieldWarnings['purchase_date'] = ['confidence' => 'low', 'warning' => 'Could not extract purchase date'];
        }

        // purchase_time: high if extracted, low if null
        if ($result->time !== null) {
            $fieldWarnings['purchase_time'] = ['confidence' => 'high', 'warning' => null];
        } else {
            $fieldWarnings['purchase_time'] = ['confidence' => 'low', 'warning' => 'Could not extract purchase time'];
        }

        // subtotal: high if > 0, medium if 0 (might be computed/missing)
        if ($result->subtotal > 0) {
            $fieldWarnings['subtotal'] = ['confidence' => 'high', 'warning' => null];
        } else {
            $fieldWarnings['subtotal'] = ['confidence' => 'medium', 'warning' => 'Subtotal is zero or not detected'];
        }

        // total: high if > 0, low if 0
        if ($result->total > 0) {
            $fieldWarnings['total'] = ['confidence' => 'high', 'warning' => null];
        } else {
            $fieldWarnings['total'] = ['confidence' => 'low', 'warning' => 'Total is zero or not detected'];
        }

        // items: high if not empty and most have high confidence, medium if some have warnings, low if empty
        $items = $result->items;
        if (empty($items)) {
            $fieldWarnings['items'] = ['confidence' => 'low', 'warning' => 'No items detected'];
        } else {
            $lowConfidenceCount = 0;
            foreach ($items as $item) {
                if ($item->confidence === 'low' || $item->warning !== null) {
                    $lowConfidenceCount++;
                }
            }
            if ($lowConfidenceCount === 0) {
                $fieldWarnings['items'] = ['confidence' => 'high', 'warning' => null];
            } elseif ($lowConfidenceCount < count($items) / 2) {
                $fieldWarnings['items'] = ['confidence' => 'medium', 'warning' => "$lowConfidenceCount item(s) have warnings"];
            } else {
                $fieldWarnings['items'] = ['confidence' => 'low', 'warning' => 'Many items have low confidence'];
            }
        }

        return $fieldWarnings;
    }

    /**
     * Get list of supported shops for receipt parsing.
     */
    public function supportedShops(): JsonResponse
    {
        return response()->json([
            'data' => $this->importService->getSupportedShops(),
        ]);
    }
}
