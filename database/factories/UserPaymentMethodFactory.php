<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserPaymentMethod>
 */
class UserPaymentMethodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $paymentTypes = [
            'DKB Card',
            'N26 Main',
            'PayPal Account',
            'EC Card Primary',
            'Amazon VISA',
            'Cash Wallet',
        ];

        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement($paymentTypes) . ' ' . fake()->numberBetween(1, 99),
            'notes' => fake()->optional(0.3)->sentence(),
            'is_active' => fake()->boolean(90), // 90% active
            'payment_method_id' => null, // Can be set via state method
        ];
    }

    /**
     * Indicate that the payment method belongs to a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Indicate that the payment method references a canonical payment method.
     */
    public function withCanonicalMethod(PaymentMethod $method): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method_id' => $method->id,
        ]);
    }

    /**
     * Indicate that the payment method is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
