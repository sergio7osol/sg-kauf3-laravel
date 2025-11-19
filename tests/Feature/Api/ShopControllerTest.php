<?php

namespace Tests\Feature\Api;

use App\Enums\CountryCode;
use App\Enums\PurchaseChannel;
use App\Models\Shop;
use App\Models\ShopAddress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShopControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticate(): void
    {
        Sanctum::actingAs(User::factory()->create());
    }

    public function test_it_lists_active_shops_and_includes_addresses_when_requested(): void
    {
        $this->authenticate();

        $activeShop = Shop::factory()->create([
            'display_order' => 1,
            'is_active' => true,
        ]);
        ShopAddress::factory()->count(2)->forShop($activeShop)->create();

        Shop::factory()->inactive()->create(['display_order' => 2]);

        $response = $this->getJson('/api/shops?includeAddresses=1');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.count', 1)
            ->assertJsonPath('data.0.id', $activeShop->id)
            ->assertJsonCount(2, 'data.0.addresses');
    }

    public function test_it_filters_shops_by_country_and_type(): void
    {
        $this->authenticate();

        $germanyShop = Shop::factory()->create([
            'country' => CountryCode::GERMANY->value,
            'type' => PurchaseChannel::IN_STORE->value,
            'display_order' => 1,
            'is_active' => true,
        ]);

        Shop::factory()->create([
            'country' => CountryCode::RUSSIA->value,
            'type' => PurchaseChannel::ONLINE->value,
            'display_order' => 2,
            'is_active' => true,
        ]);

        $response = $this->getJson(
            '/api/shops?country=' . CountryCode::GERMANY->value . '&type=' . PurchaseChannel::IN_STORE->value
        );

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $germanyShop->id);
    }

    public function test_it_shows_a_single_shop_with_optional_addresses(): void
    {
        $this->authenticate();

        $shop = Shop::factory()->create(['display_order' => 5]);
        ShopAddress::factory()->forShop($shop)->create();

        $response = $this->getJson('/api/shops/' . $shop->id . '?includeAddresses=1');

        $response->assertOk()
            ->assertJsonPath('data.id', $shop->id)
            ->assertJsonCount(1, 'data.addresses');
    }
}
