<?php

namespace App\Services\Shop;

use App\Models\Shop;
use App\Models\ShopAddress;
use Illuminate\Support\Facades\DB;

/**
 * Handles business logic for shop address management.
 * Encapsulates transactional operations and invariant enforcement.
 */
class ShopAddressService
{
    /**
     * Create a new address for a shop.
     * Automatically assigns next display_order if not provided.
     * If isPrimary is true, unsets other primary addresses first.
     */
    public function create(Shop $shop, array $data): ShopAddress
    {
        return DB::transaction(function () use ($shop, $data) {
            // Auto-assign display_order if not provided
            if (!isset($data['display_order'])) {
                $maxOrder = $shop->addresses()->max('display_order') ?? 0;
                $data['display_order'] = $maxOrder + 1;
            }

            // Handle primary flag
            $isPrimary = $data['is_primary'] ?? false;
            if ($isPrimary) {
                $this->unsetPrimaryForShop($shop);
            }

            $data['shop_id'] = $shop->id;

            return ShopAddress::create($data);
        });
    }

    /**
     * Update an existing address.
     * If isPrimary is being set to true, unsets other primary addresses first.
     */
    public function update(ShopAddress $address, array $data): ShopAddress
    {
        return DB::transaction(function () use ($address, $data) {
            // Handle primary flag change
            $settingPrimary = isset($data['is_primary']) && $data['is_primary'] && !$address->is_primary;
            if ($settingPrimary) {
                $this->unsetPrimaryForShop($address->shop);
            }

            $address->update($data);

            return $address->fresh();
        });
    }

    /**
     * Set an address as the primary for its shop.
     * Transactionally unsets any existing primary address.
     */
    public function setPrimary(ShopAddress $address): ShopAddress
    {
        return DB::transaction(function () use ($address) {
            $this->unsetPrimaryForShop($address->shop);
            
            $address->update(['is_primary' => true]);

            return $address->fresh();
        });
    }

    /**
     * Toggle the active status of an address.
     */
    public function toggleActive(ShopAddress $address): ShopAddress
    {
        $address->update(['is_active' => !$address->is_active]);

        return $address->fresh();
    }

    /**
     * Unset primary flag for all addresses of a shop.
     */
    private function unsetPrimaryForShop(Shop $shop): void
    {
        $shop->addresses()
            ->where('is_primary', true)
            ->update(['is_primary' => false]);
    }
}
