<?php

namespace Database\Factories;

use App\Models\Purchase;
use App\Models\Shop;
use App\Models\ShopAddress;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Purchase>
 */
class PurchaseFactory extends Factory
{
    protected $model = Purchase::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'shop_id' => Shop::factory(),
            'shop_address_id' => function (array $attributes) {
                // Create an address for the shop if not provided
                return ShopAddress::factory()->create([
                    'shop_id' => $attributes['shop_id'],
                ])->id;
            },
            'purchase_date' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'currency' => 'EUR',
            'status' => $this->faker->randomElement(['draft', 'confirmed', 'cancelled']),
            'subtotal' => 0, // Will be calculated from lines
            'tax_amount' => 0, // Will be calculated from lines
            'total_amount' => 0, // Will be calculated from lines
            'notes' => $this->faker->optional(0.3)->sentence(),
            'receipt_number' => $this->faker->optional(0.7)->numerify('RCP-####-####'),
        ];
    }

    /**
     * Force the purchase to be confirmed.
     */
    public function confirmed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
        ]);
    }

    /**
     * Force the purchase to be a draft.
     */
    public function draft(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    /**
     * Attach to an existing user.
     */
    public function forUser(User $user): self
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Attach to an existing shop and one of its addresses.
     */
    public function forShop(Shop $shop, ?ShopAddress $address = null): self
    {
        return $this->state(fn (array $attributes) => [
            'shop_id' => $shop->id,
            'shop_address_id' => $address?->id ?? ShopAddress::factory()->create(['shop_id' => $shop->id])->id,
        ]);
    }

    /**
     * Set specific totals (useful for testing).
     */
    public function withTotals(int $subtotal, int $taxAmount): self
    {
        return $this->state(fn (array $attributes) => [
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $subtotal + $taxAmount,
        ]);
    }
}
