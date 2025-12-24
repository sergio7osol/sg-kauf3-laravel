<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreShopAddressRequest;
use App\Http\Requests\UpdateShopAddressRequest;
use App\Models\Shop;
use App\Models\ShopAddress;
use App\Services\Shop\ShopAddressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopAddressController extends Controller
{
    public function __construct(
        private readonly ShopAddressService $addressService,
    ) {}

    /**
     * Display a listing of addresses for a shop.
     */
    public function index(Request $request, Shop $shop): JsonResponse
    {
        $validated = $request->validate([
            'activeOnly' => ['nullable', 'boolean'],
        ]);

        $query = $shop->addresses()
            ->orderBy('display_order')
            ->orderBy('id');

        if ($request->boolean('activeOnly', false)) {
            $query->where('is_active', true);
        }

        $addresses = $query->get()
            ->map(fn (ShopAddress $address) => $address->toData()->toArray())
            ->values();

        return response()->json([
            'data' => $addresses,
            'meta' => [
                'count' => $addresses->count(),
                'shopId' => $shop->id,
            ],
        ]);
    }

    /**
     * Store a newly created address for a shop.
     */
    public function store(StoreShopAddressRequest $request, Shop $shop): JsonResponse
    {
        $data = $request->validatedSnakeCase();

        $address = $this->addressService->create($shop, $data);

        return response()->json([
            'data' => $address->toData()->toArray(),
            'message' => 'Address created successfully.',
        ], 201);
    }

    /**
     * Display the specified address.
     */
    public function show(Shop $shop, ShopAddress $address): JsonResponse
    {
        // Ensure address belongs to shop
        if ($address->shop_id !== $shop->id) {
            return response()->json([
                'message' => 'Address not found for this shop.',
            ], 404);
        }

        return response()->json([
            'data' => $address->toData()->toArray(),
        ]);
    }

    /**
     * Update the specified address.
     */
    public function update(UpdateShopAddressRequest $request, Shop $shop, ShopAddress $address): JsonResponse
    {
        // Ensure address belongs to shop
        if ($address->shop_id !== $shop->id) {
            return response()->json([
                'message' => 'Address not found for this shop.',
            ], 404);
        }

        $data = $request->validatedSnakeCase();

        $address = $this->addressService->update($address, $data);

        return response()->json([
            'data' => $address->toData()->toArray(),
            'message' => 'Address updated successfully.',
        ]);
    }

    /**
     * Set the specified address as primary for its shop.
     */
    public function setPrimary(Shop $shop, ShopAddress $address): JsonResponse
    {
        // Ensure address belongs to shop
        if ($address->shop_id !== $shop->id) {
            return response()->json([
                'message' => 'Address not found for this shop.',
            ], 404);
        }

        $address = $this->addressService->setPrimary($address);

        return response()->json([
            'data' => $address->toData()->toArray(),
            'message' => 'Address set as primary successfully.',
        ]);
    }

    /**
     * Toggle the active status of the specified address.
     */
    public function toggleActive(Shop $shop, ShopAddress $address): JsonResponse
    {
        // Ensure address belongs to shop
        if ($address->shop_id !== $shop->id) {
            return response()->json([
                'message' => 'Address not found for this shop.',
            ], 404);
        }

        $address = $this->addressService->toggleActive($address);

        $status = $address->is_active ? 'activated' : 'deactivated';

        return response()->json([
            'data' => $address->toData()->toArray(),
            'message' => "Address {$status} successfully.",
        ]);
    }
}
