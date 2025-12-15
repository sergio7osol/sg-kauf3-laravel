<?php

namespace App\Http\Controllers\Api;

use App\DTO\Shop\ShopAddressData;
use App\DTO\Shop\ShopData;
use App\Enums\CountryCode;
use App\Enums\PurchaseChannel;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreShopRequest;
use App\Models\Shop;
use App\Support\CaseConverter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
    public function store(StoreShopRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $data = CaseConverter::toSnakeCase($validated);

        $slug = $this->generateUniqueSlug(
            name: $data['name'],
            providedSlug: $data['slug'] ?? null,
        );

        $nextOrder = Shop::max('display_order');
        $displayOrder = $data['display_order'] ?? (($nextOrder ?? -1) + 1);

        $shop = Shop::create([
            'name' => $data['name'],
            'slug' => $slug,
            'type' => $data['type'],
            'country' => $data['country'],
            'display_order' => $displayOrder,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return response()->json([
            'data' => $this->transformShop($shop),
        ], 201);
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
     * Generate a unique slug using the provided name/slug pair.
     */
    protected function generateUniqueSlug(string $name, ?string $providedSlug = null): string
    {
        $base = $providedSlug ?: Str::slug($name);
        $slug = $base;
        $suffix = 2;

        while (Shop::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }
}
