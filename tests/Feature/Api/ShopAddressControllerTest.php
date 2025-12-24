<?php

namespace Tests\Feature\Api;

use App\Enums\CountryCode;
use App\Models\Shop;
use App\Models\ShopAddress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShopAddressControllerTest extends TestCase
{
    use RefreshDatabase;

    private Shop $shop;

    protected function setUp(): void
    {
        parent::setUp();
        $this->shop = Shop::factory()->create();
    }

    protected function authenticate(): void
    {
        Sanctum::actingAs(User::factory()->create());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // INDEX
    // ─────────────────────────────────────────────────────────────────────────

    public function test_it_lists_addresses_for_a_shop(): void
    {
        $this->authenticate();

        ShopAddress::factory()->count(3)->forShop($this->shop)->create();

        $response = $this->getJson("/api/shops/{$this->shop->id}/addresses");

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.count', 3)
            ->assertJsonPath('meta.shopId', $this->shop->id);
    }

    public function test_it_filters_active_only_addresses(): void
    {
        $this->authenticate();

        ShopAddress::factory()->forShop($this->shop)->create(['is_active' => true]);
        ShopAddress::factory()->forShop($this->shop)->create(['is_active' => false]);

        $response = $this->getJson("/api/shops/{$this->shop->id}/addresses?activeOnly=1");

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_it_returns_camel_case_keys_in_response(): void
    {
        $this->authenticate();

        ShopAddress::factory()->forShop($this->shop)->create([
            'postal_code' => '12345',
            'house_number' => '10A',
            'is_primary' => true,
            'display_order' => 1,
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/shops/{$this->shop->id}/addresses");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'shopId',
                        'country',
                        'postalCode',
                        'city',
                        'street',
                        'houseNumber',
                        'isPrimary',
                        'displayOrder',
                        'isActive',
                    ],
                ],
            ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STORE
    // ─────────────────────────────────────────────────────────────────────────

    public function test_it_creates_an_address_for_a_shop(): void
    {
        $this->authenticate();

        $payload = [
            'postalCode' => '10115',
            'city' => 'Berlin',
            'street' => 'Friedrichstraße',
            'houseNumber' => '123',
        ];

        $response = $this->postJson("/api/shops/{$this->shop->id}/addresses", $payload);

        $response->assertCreated()
            ->assertJsonPath('data.postalCode', '10115')
            ->assertJsonPath('data.city', 'Berlin')
            ->assertJsonPath('data.shopId', $this->shop->id);

        $this->assertDatabaseHas('shop_addresses', [
            'shop_id' => $this->shop->id,
            'postal_code' => '10115',
            'city' => 'Berlin',
        ]);
    }

    public function test_it_auto_assigns_display_order_when_not_provided(): void
    {
        $this->authenticate();

        // Create first address with display_order = 5
        ShopAddress::factory()->forShop($this->shop)->create(['display_order' => 5]);

        $payload = [
            'postalCode' => '10117',
            'city' => 'Berlin',
            'street' => 'Unter den Linden',
            'houseNumber' => '1',
        ];

        $response = $this->postJson("/api/shops/{$this->shop->id}/addresses", $payload);

        $response->assertCreated()
            ->assertJsonPath('data.displayOrder', 6);
    }

    public function test_it_defaults_country_to_germany(): void
    {
        $this->authenticate();

        $payload = [
            'postalCode' => '10115',
            'city' => 'Berlin',
            'street' => 'Friedrichstraße',
            'houseNumber' => '123',
        ];

        $response = $this->postJson("/api/shops/{$this->shop->id}/addresses", $payload);

        $response->assertCreated()
            ->assertJsonPath('data.country', CountryCode::GERMANY->value);
    }

    public function test_it_validates_required_fields_on_create(): void
    {
        $this->authenticate();

        $response = $this->postJson("/api/shops/{$this->shop->id}/addresses", []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['postalCode', 'city', 'street', 'houseNumber']);
    }

    public function test_it_prevents_duplicate_addresses_for_same_shop(): void
    {
        $this->authenticate();

        ShopAddress::factory()->forShop($this->shop)->create([
            'postal_code' => '10115',
            'street' => 'Friedrichstraße',
            'house_number' => '123',
        ]);

        $payload = [
            'postalCode' => '10115',
            'city' => 'Berlin',
            'street' => 'Friedrichstraße',
            'houseNumber' => '123',
        ];

        $response = $this->postJson("/api/shops/{$this->shop->id}/addresses", $payload);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['address']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SHOW
    // ─────────────────────────────────────────────────────────────────────────

    public function test_it_shows_a_single_address(): void
    {
        $this->authenticate();

        $address = ShopAddress::factory()->forShop($this->shop)->create();

        $response = $this->getJson("/api/shops/{$this->shop->id}/addresses/{$address->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $address->id);
    }

    public function test_it_returns_404_for_address_not_belonging_to_shop(): void
    {
        $this->authenticate();

        $otherShop = Shop::factory()->create();
        $address = ShopAddress::factory()->forShop($otherShop)->create();

        $response = $this->getJson("/api/shops/{$this->shop->id}/addresses/{$address->id}");

        $response->assertNotFound();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // UPDATE
    // ─────────────────────────────────────────────────────────────────────────

    public function test_it_updates_an_address(): void
    {
        $this->authenticate();

        $address = ShopAddress::factory()->forShop($this->shop)->create([
            'city' => 'Berlin',
        ]);

        $response = $this->putJson("/api/shops/{$this->shop->id}/addresses/{$address->id}", [
            'city' => 'Munich',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.city', 'Munich');

        $this->assertDatabaseHas('shop_addresses', [
            'id' => $address->id,
            'city' => 'Munich',
        ]);
    }

    public function test_it_prevents_update_to_duplicate_address(): void
    {
        $this->authenticate();

        ShopAddress::factory()->forShop($this->shop)->create([
            'postal_code' => '10115',
            'street' => 'Friedrichstraße',
            'house_number' => '123',
        ]);

        $address = ShopAddress::factory()->forShop($this->shop)->create([
            'postal_code' => '10117',
            'street' => 'Unter den Linden',
            'house_number' => '1',
        ]);

        $response = $this->putJson("/api/shops/{$this->shop->id}/addresses/{$address->id}", [
            'postalCode' => '10115',
            'street' => 'Friedrichstraße',
            'houseNumber' => '123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['address']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SET PRIMARY
    // ─────────────────────────────────────────────────────────────────────────

    public function test_it_sets_address_as_primary(): void
    {
        $this->authenticate();

        $address1 = ShopAddress::factory()->forShop($this->shop)->create(['is_primary' => true]);
        $address2 = ShopAddress::factory()->forShop($this->shop)->create(['is_primary' => false]);

        $response = $this->patchJson("/api/shops/{$this->shop->id}/addresses/{$address2->id}/set-primary");

        $response->assertOk()
            ->assertJsonPath('data.isPrimary', true);

        $this->assertDatabaseHas('shop_addresses', ['id' => $address1->id, 'is_primary' => false]);
        $this->assertDatabaseHas('shop_addresses', ['id' => $address2->id, 'is_primary' => true]);
    }

    public function test_it_ensures_only_one_primary_per_shop(): void
    {
        $this->authenticate();

        // Create 3 addresses, all non-primary
        $addresses = ShopAddress::factory()->count(3)->forShop($this->shop)->create(['is_primary' => false]);

        // Set the second one as primary
        $this->patchJson("/api/shops/{$this->shop->id}/addresses/{$addresses[1]->id}/set-primary");

        // Set the third one as primary
        $this->patchJson("/api/shops/{$this->shop->id}/addresses/{$addresses[2]->id}/set-primary");

        // Only the third should be primary now
        $this->assertEquals(1, ShopAddress::where('shop_id', $this->shop->id)->where('is_primary', true)->count());
        $this->assertDatabaseHas('shop_addresses', ['id' => $addresses[2]->id, 'is_primary' => true]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TOGGLE ACTIVE
    // ─────────────────────────────────────────────────────────────────────────

    public function test_it_toggles_active_status(): void
    {
        $this->authenticate();

        $address = ShopAddress::factory()->forShop($this->shop)->create(['is_active' => true]);

        // Deactivate
        $response = $this->patchJson("/api/shops/{$this->shop->id}/addresses/{$address->id}/toggle-active");
        $response->assertOk()
            ->assertJsonPath('data.isActive', false);

        // Reactivate
        $response = $this->patchJson("/api/shops/{$this->shop->id}/addresses/{$address->id}/toggle-active");
        $response->assertOk()
            ->assertJsonPath('data.isActive', true);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AUTH
    // ─────────────────────────────────────────────────────────────────────────

    public function test_it_requires_authentication(): void
    {
        $response = $this->getJson("/api/shops/{$this->shop->id}/addresses");

        $response->assertUnauthorized();
    }
}
