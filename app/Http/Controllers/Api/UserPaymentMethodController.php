<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserPaymentMethodRequest;
use App\Http\Requests\UpdateUserPaymentMethodRequest;
use App\Models\UserPaymentMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserPaymentMethodController extends Controller
{
    /**
     * Display a listing of the authenticated user's payment methods.
     */
    public function index(Request $request): JsonResponse
    {
        $methods = UserPaymentMethod::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get()
            ->map(fn($method) => $method->toData()->toArray());

        return response()->json([
            'data' => $methods,
            'meta' => [
                'count' => $methods->count(),
            ],
        ]);
    }

    /**
     * Store a newly created user payment method.
     */
    public function store(StoreUserPaymentMethodRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $method = UserPaymentMethod::create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'notes' => $validated['notes'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'payment_method_id' => $validated['payment_method_id'] ?? null,
        ]);

        return response()->json([
            'data' => $method->toData()->toArray(),
        ], 201);
    }

    /**
     * Display the specified user payment method.
     */
    public function show(Request $request, UserPaymentMethod $userPaymentMethod): JsonResponse
    {
        // Authorize: user can only view their own payment methods
        if ($userPaymentMethod->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized access to payment method.',
            ], 403);
        }

        return response()->json([
            'data' => $userPaymentMethod->toData()->toArray(),
        ]);
    }

    /**
     * Update the specified user payment method.
     */
    public function update(UpdateUserPaymentMethodRequest $request, UserPaymentMethod $userPaymentMethod): JsonResponse
    {
        // Authorize: user can only update their own payment methods
        if ($userPaymentMethod->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized access to payment method.',
            ], 403);
        }

        $validated = $request->validated();

        $userPaymentMethod->update($validated);

        return response()->json([
            'data' => $userPaymentMethod->fresh()->toData()->toArray(),
        ]);
    }

    /**
     * Remove the specified user payment method.
     */
    public function destroy(Request $request, UserPaymentMethod $userPaymentMethod): JsonResponse
    {
        // Authorize: user can only delete their own payment methods
        if ($userPaymentMethod->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized access to payment method.',
            ], 403);
        }

        $userPaymentMethod->delete();

        return response()->json([
            'message' => 'Payment method deleted successfully.',
        ]);
    }
}
