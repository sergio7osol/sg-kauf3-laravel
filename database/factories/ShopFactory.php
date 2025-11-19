<?php

namespace Database\Factories;

use App\Enums\CountryCode;
use App\Enums\PurchaseChannel;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Shop>
 */
class ShopFactory extends Factory
{
    protected $model = Shop::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->company();
        $channel = $this->faker->randomElement(PurchaseChannel::cases());

        $country = $this->faker->optional()
            ->randomElement(CountryCode::cases())
            ?->value
            ?? CountryCode::GERMANY->value;

        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . $this->faker->unique()->numberBetween(100, 999),
            'type' => $channel->value,
            'country' => $country,
            'display_order' => $this->faker->numberBetween(0, 200),
            'is_active' => $this->faker->boolean(90),
        ];
    }

    /**
     * Mark the shop as inactive.
     */
    public function inactive(): self
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    /**
     * Force an in-store purchase channel.
     */
    public function inStore(): self
    {
        return $this->state(fn () => ['type' => PurchaseChannel::IN_STORE->value]);
    }

    /**
     * Force an online purchase channel.
     */
    public function online(): self
    {
        return $this->state(fn () => ['type' => PurchaseChannel::ONLINE->value]);
    }
}
