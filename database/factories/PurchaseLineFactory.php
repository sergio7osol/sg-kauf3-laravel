<?php

namespace Database\Factories;

use App\Models\Purchase;
use App\Models\PurchaseLine;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseLine>
 */
class PurchaseLineFactory extends Factory
{
    protected $model = PurchaseLine::class;

    /**
     * Define the model's default state.
     * Note: line_amount, discount_amount, and tax_amount are auto-calculated in the model's booted() hook.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unitPriceCents = $this->faker->numberBetween(50, 5000); // €0.50 to €50.00
        $quantity = $this->faker->randomFloat(3, 0.1, 10); // 0.1 to 10 units
        $taxRate = $this->faker->randomElement([0, 7, 19]); // Common VAT rates in Germany

        return [
            'purchase_id' => Purchase::factory(),
            'line_number' => 1,
            'product_id' => null, // Optional product reference
            'description' => $this->faker->words(3, true),
            'quantity' => $quantity,
            'unit_price' => $unitPriceCents,
            'tax_rate' => $taxRate,
            'discount_percent' => null,
            'notes' => $this->faker->optional(0.2)->sentence(),
            // line_amount, discount_amount, tax_amount calculated automatically on save
        ];
    }

    /**
     * Attach to an existing purchase with a specific line number.
     */
    public function forPurchase(Purchase $purchase, int $lineNumber = 1): self
    {
        return $this->state(fn (array $attributes) => [
            'purchase_id' => $purchase->id,
            'line_number' => $lineNumber,
        ]);
    }

    /**
     * Link to an existing product.
     */
    public function withProduct(Product $product): self
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $product->id,
            'description' => $product->name ?? $attributes['description'],
        ]);
    }

    /**
     * Apply a percentage discount to this line.
     */
    public function withDiscount(float $percent): self
    {
        return $this->state(fn (array $attributes) => [
            'discount_percent' => $percent,
        ]);
    }

    /**
     * Set specific quantity and price (useful for testing exact totals).
     */
    public function withQuantityAndPrice(float $quantity, int $unitPriceCents): self
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $quantity,
            'unit_price' => $unitPriceCents,
        ]);
    }

    /**
     * Use a specific tax rate.
     */
    public function withTaxRate(float $rate): self
    {
        return $this->state(fn (array $attributes) => [
            'tax_rate' => $rate,
        ]);
    }
}
