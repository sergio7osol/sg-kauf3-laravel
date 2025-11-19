<?php

namespace Database\Factories;

use App\Enums\CountryCode;
use App\Models\Shop;
use App\Models\ShopAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShopAddress>
 */
class ShopAddressFactory extends Factory
{
    protected $model = ShopAddress::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'country' => $this->faker->randomElement(CountryCode::cases())->value,
            'postal_code' => $this->faker->postcode(),
            'city' => $this->faker->city(),
            'street' => $this->faker->streetName(),
            'house_number' => $this->faker->buildingNumber(),
            'is_primary' => $this->faker->boolean(60),
            'display_order' => $this->faker->numberBetween(1, 5),
            'is_active' => true,
        ];
    }

    /**
     * Force the address to be primary.
     */
    public function primary(): self
    {
        return $this->state(fn () => ['is_primary' => true, 'display_order' => 1]);
    }

    /**
     * Attach to an existing shop.
     */
    public function forShop(Shop $shop): self
    {
        return $this->state(fn () => ['shop_id' => $shop->id]);
    }
}
