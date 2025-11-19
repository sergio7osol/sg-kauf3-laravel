<?php

namespace App\Http\Controllers\Api;

use App\DTO\Shop\ShopAddressData;
use App\DTO\Shop\ShopData;
use App\Enums\CountryCode;
use App\Enums\PurchaseChannel;
use App\Http\Controllers\Controller;
use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShopController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'country' => ['nullable', Rule::enum(CountryCode::class)],
            'type' => ['nullable', Rule::enum(PurchaseChannel::class)],
            'includeAddresses' => ['nullable', 'boolean'],
        ]);

        $includeAddresses = (bool) ($validated['includeAddresses'] ?? false);

        $query = Shop::query()
            ->where('is_active', true)
            ->when(isset($validated['country']), fn ($q) => $q->where('country', $validated['country']))
            ->when(isset($validated['type']), fn ($q) => $q->where('type', $validated['type']))
            ->orderBy('display_order')
            ->orderBy('name');

        if ($includeAddresses) {
            $query->with(['addresses' => fn ($relation) => $relation
                ->orderBy('display_order')
                ->orderBy('id')
            ]);
        }

        $data = $query->get()
            ->map(fn (Shop $shop) => $this->transformShop($shop, $includeAddresses))
            ->values();

        return response()->json([
            'data' => $data,
            'meta' => [
                'count' => $data->count(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Shop $shop): JsonResponse
    {
        $includeAddresses = $request->boolean('includeAddresses');

        if ($includeAddresses) {
            $shop->load(['addresses' => fn ($relation) => $relation
                ->orderBy('display_order')
                ->orderBy('id')
            ]);
        }

        return response()->json([
            'data' => $this->transformShop($shop, $includeAddresses),
        ]);
    }

    /**
     * Transform a shop model into an API payload using DTOs.
     */
    protected function transformShop(Shop $shop, bool $includeAddresses = false): array
    {
        $payload = $shop->toData()->toArray();

        if ($includeAddresses) {
            $addresses = $shop->addresses
                ->map(fn ($address) => $address->toData()->toArray())
                ->all();

            $payload['addresses'] = $addresses;
        }

        return $payload;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
