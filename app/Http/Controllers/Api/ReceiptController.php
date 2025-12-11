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
     * Get list of supported shops for receipt parsing.
     */
    public function supportedShops(): JsonResponse
    {
        return response()->json([
            'data' => $this->importService->getSupportedShops(),
        ]);
    }
}
