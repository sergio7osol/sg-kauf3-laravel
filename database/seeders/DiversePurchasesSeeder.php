<?php

namespace Database\Seeders;

use App\Models\Purchase;
use App\Models\PurchaseLine;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Seeder;

class DiversePurchasesSeeder extends Seeder
{
    /**
     * Seed diverse purchases across different shops and addresses.
     */
    public function run(): void
    {
        // Get or create a user
        $user = User::first() ?? User::factory()->create();

        // Use existing shops/addresses seeded via ShopAddressSeeder
        $shops = Shop::with(['addresses' => fn ($query) => $query->orderBy('display_order')])
            ->whereHas('addresses')
            ->get();

        if ($shops->isEmpty()) {
            $this->command->warn('No shops with addresses found. Run ShopSeeder and ShopAddressSeeder first.');
            return;
        }

        // Create 10-15 purchases distributed across real shops/addresses
        for ($i = 0; $i < 15; $i++) {
            $shop = $shops->random();
            $address = $shop->addresses->random();

            $purchase = Purchase::factory()
                ->forUser($user)
                ->forShop($shop, $address)
                ->create();

            // Add 1-3 line items per purchase
            $lineCount = rand(1, 3);
            for ($line = 1; $line <= $lineCount; $line++) {
                PurchaseLine::factory()
                    ->forPurchase($purchase, $line)
                    ->create();
            }

            // Recalculate totals from lines
            $purchase->refresh();
            $purchase->recalculateTotals();
        }

        $this->command->info('Created 15 diverse purchases across 5 shops!');
    }
}
