<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePurchaseRequest;
use App\Http\Requests\UpdatePurchaseRequest;
use App\Models\Purchase;
use App\Models\PurchaseLine;
use App\Support\CaseConverter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PurchaseController extends Controller
{
    /**
     * Get the date range of all purchases for the authenticated user.
     * Useful for "All Time" chart view without fetching all records.
     */
    public function dateRange(Request $request): JsonResponse
    {
        $range = Purchase::where('user_id', $request->user()->id)
            ->selectRaw('MIN(purchase_date) as earliest_date, MAX(purchase_date) as latest_date, COUNT(*) as total_count')
            ->first();

        return response()->json([
            'data' => [
                'earliestDate' => $range->earliest_date,
                'latestDate' => $range->latest_date,
                'totalCount' => (int) $range->total_count,
            ],
        ]);
    }

    /**
     * Display a listing of purchases with optional filters.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shopId' => ['nullable', 'integer', 'exists:shops,id'],
            'dateFrom' => ['nullable', 'date'],
            'dateTo' => ['nullable', 'date', 'after_or_equal:dateFrom'],
            'status' => ['nullable', Rule::in(['draft', 'confirmed', 'cancelled'])],
            'includeLines' => ['nullable', 'boolean'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $includeLines = (bool) ($validated['includeLines'] ?? false);
        $perPage = $validated['perPage'] ?? 15;

        $query = Purchase::query()
            ->where('user_id', $request->user()->id)
            ->when(isset($validated['shopId']), fn ($q) => $q->where('shop_id', $validated['shopId']))
            ->when(isset($validated['dateFrom']), fn ($q) => $q->where('purchase_date', '>=', $validated['dateFrom']))
            ->when(isset($validated['dateTo']), fn ($q) => $q->where('purchase_date', '<=', $validated['dateTo']))
            ->when(isset($validated['status']), fn ($q) => $q->where('status', $validated['status']))
            ->orderBy('purchase_date', 'desc')
            ->orderBy('id', 'desc');

        // Eager-load relationships to avoid N+1
        $query->with(['shop', 'shopAddress', 'userPaymentMethod']);

        if ($includeLines) {
            $query->with(['lines' => fn ($relation) => $relation->orderBy('line_number')]);
        }

        $purchases = $query->paginate($perPage);

        $data = $purchases->getCollection()
            ->map(fn (Purchase $purchase) => $purchase->toData($includeLines)->toArray())
            ->values();

        return response()->json([
            'data' => $data,
            'meta' => [
                'currentPage' => $purchases->currentPage(),
                'from' => $purchases->firstItem(),
                'lastPage' => $purchases->lastPage(),
                'perPage' => $purchases->perPage(),
                'to' => $purchases->lastItem(),
                'total' => $purchases->total(),
            ],
        ]);
    }

    /**
     * Display the specified purchase with full details.
     */
    public function show(Request $request, Purchase $purchase): JsonResponse
    {
        // Ensure the purchase belongs to the authenticated user
        if ($purchase->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Purchase not found.',
            ], 404);
        }

        // Eager-load relationships
        $purchase->load([
            'lines' => fn ($query) => $query->orderBy('line_number'),
            'shop',
            'shopAddress',
            'userPaymentMethod',
        ]);

        return response()->json([
            'data' => $purchase->toData(includeLines: true)->toArray(),
        ]);
    }

    /**
     * Store a newly created purchase in storage.
     * Creates purchase + lines atomically within a transaction.
     */
    public function store(StorePurchaseRequest $request): JsonResponse
    {
        try {
            $purchase = DB::transaction(function () use ($request) {
                $validated = $request->validated();
                $data = CaseConverter::toSnakeCase($validated);

                // Create the purchase (totals will be recalculated after lines are saved)
                $purchase = Purchase::create([
                    'user_id' => $request->user()->id,
                    'shop_id' => $data['shop_id'],
                    'shop_address_id' => $data['shop_address_id'],
                    'user_payment_method_id' => $data['user_payment_method_id'] ?? null,
                    'purchase_date' => $data['purchase_date'],
                    'purchase_time' => $data['purchase_time'] ?? null,
                    'currency' => $data['currency'] ?? 'EUR',
                    'status' => $data['status'] ?? 'confirmed',
                    'notes' => $data['notes'] ?? null,
                    'receipt_number' => $data['receipt_number'] ?? null,
                    'subtotal' => 0,
                    'tax_amount' => 0,
                    'total_amount' => 0,
                ]);

                // Create each line item
                // The PurchaseLine model's booted() hook will auto-calculate amounts
                foreach ($data['lines'] as $lineData) {
                    PurchaseLine::create([
                        'purchase_id' => $purchase->id,
                        'line_number' => $lineData['line_number'],
                        'product_id' => $lineData['product_id'] ?? null,
                        'description' => $lineData['description'],
                        'quantity' => $lineData['quantity'],
                        'unit_price' => $lineData['unit_price'],
                        'tax_rate' => $lineData['tax_rate'],
                        'discount_percent' => $lineData['discount_percent'] ?? null,
                        'discount_amount' => $lineData['discount_amount'] ?? null,
                        'notes' => $lineData['notes'] ?? null,
                    ]);
                }

                // Recalculate purchase totals from the saved lines
                $purchase->refresh();
                $purchase->recalculateTotals();

                return $purchase;
            });

            // Load lines for response
            $purchase->load('lines');

            return response()->json([
                'data' => $purchase->toData(includeLines: true)->toArray(),
                'message' => 'Purchase created successfully.',
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create purchase.',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred.',
            ], 500);
        }
    }

    /**
     * Update the specified purchase.
     * Supports partial updates; if lines are provided, replaces all existing lines.
     */
    public function update(UpdatePurchaseRequest $request, Purchase $purchase): JsonResponse
    {
        // Ensure the purchase belongs to the authenticated user
        if ($purchase->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Purchase not found.',
            ], 404);
        }

        try {
            $purchase = DB::transaction(function () use ($request, $purchase) {
                $validated = $request->validated();
                $data = CaseConverter::toSnakeCase($validated);

                // Update header fields (only those provided)
                $headerFields = [
                    'shop_id',
                    'shop_address_id',
                    'user_payment_method_id',
                    'purchase_date',
                    'purchase_time',
                    'currency',
                    'status',
                    'notes',
                    'receipt_number',
                ];

                $updateData = [];
                foreach ($headerFields as $field) {
                    if (array_key_exists($field, $data)) {
                        $updateData[$field] = $data[$field];
                    }
                }

                if (!empty($updateData)) {
                    $purchase->update($updateData);
                }

                // If lines are provided, replace all existing lines
                if (isset($data['lines']) && is_array($data['lines'])) {
                    // Delete existing lines
                    $purchase->lines()->delete();

                    // Create new lines
                    foreach ($data['lines'] as $lineData) {
                        PurchaseLine::create([
                            'purchase_id' => $purchase->id,
                            'line_number' => $lineData['line_number'],
                            'product_id' => $lineData['product_id'] ?? null,
                            'description' => $lineData['description'],
                            'quantity' => $lineData['quantity'],
                            'unit_price' => $lineData['unit_price'],
                            'tax_rate' => $lineData['tax_rate'],
                            'discount_percent' => $lineData['discount_percent'] ?? null,
                            'discount_amount' => $lineData['discount_amount'] ?? null,
                            'notes' => $lineData['notes'] ?? null,
                        ]);
                    }

                    // Recalculate purchase totals
                    $purchase->refresh();
                    $purchase->recalculateTotals();
                }

                return $purchase;
            });

            // Load relationships for response
            $purchase->load([
                'lines' => fn ($query) => $query->orderBy('line_number'),
                'shop',
                'shopAddress',
                'userPaymentMethod',
            ]);

            return response()->json([
                'data' => $purchase->toData(includeLines: true)->toArray(),
                'message' => 'Purchase updated successfully.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update purchase.',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred.',
            ], 500);
        }
    }

    /**
     * Soft delete the specified purchase.
     */
    public function destroy(Request $request, Purchase $purchase): JsonResponse
    {
        // Ensure the purchase belongs to the authenticated user
        if ($purchase->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Purchase not found.',
            ], 404);
        }

        $purchase->delete();

        return response()->json([
            'message' => 'Purchase deleted successfully.',
        ]);
    }
}
