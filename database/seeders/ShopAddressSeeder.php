<?php

namespace Database\Seeders;

use App\Enums\CountryCode;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShopAddressSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $timestamp = now();
        $shops = DB::table('shops')->pluck('id', 'name');

        $addressBlueprint = [
            // REWE locations
            [
                'shop_name' => 'REWE',
                'country' => CountryCode::GERMANY->value,
                'postal_code' => '22459',
                'city' => 'Hamburg',
                'street' => 'Tibarg',
                'house_number' => '32',
                'is_primary' => true,
                'display_order' => 1,
            ],
            [
                'shop_name' => 'REWE',
                'country' => CountryCode::GERMANY->value,
                'postal_code' => '22299',
                'city' => 'Hamburg',
                'street' => 'Osterfeldestrasse',
                'house_number' => '30-40',
                'display_order' => 2,
            ],
            [
                'shop_name' => 'REWE',
                'country' => CountryCode::GERMANY->value,
                'postal_code' => '22301',
                'city' => 'Hamburg',
                'street' => 'Dorotheenstrasse',
                'house_number' => '116-122',
                'display_order' => 3,
            ],
            [
                'shop_name' => 'REWE',
                'country' => CountryCode::GERMANY->value,
                'postal_code' => '22299',
                'city' => 'Hamburg',
                'street' => 'Fuhlsbuettler Str.',
                'house_number' => '35',
                'display_order' => 4,
            ],

            // Kaufland locations
            [
                'shop_name' => 'Kaufland',
                'country' => CountryCode::GERMANY->value,
                'postal_code' => '22529',
                'city' => 'Hamburg',
                'street' => 'Nedderfeld',
                'house_number' => '70',
                'is_primary' => true,
                'display_order' => 1,
            ],
            [
                'shop_name' => 'Kaufland',
                'country' => CountryCode::GERMANY->value,
                'postal_code' => '22761',
                'city' => 'Hamburg',
                'street' => 'Stresemannstrasse',
                'house_number' => '300',
                'display_order' => 2,
            ],
            [
                'shop_name' => 'Kaufland',
                'country' => CountryCode::GERMANY->value,
                'postal_code' => '22307',
                'city' => 'Hamburg',
                'street' => 'Fuhlsbuettler Str.',
                'house_number' => '387',
                'display_order' => 3,
            ],

            // Lidl locations
            [
                'shop_name' => 'Lidl',
                'country' => CountryCode::GERMANY->value,
                'postal_code' => '20251',
                'city' => 'Hamburg',
                'street' => 'Troplowitzstrasse',
                'house_number' => '32',
                'is_primary' => true,
                'display_order' => 1,
            ],
            [
                'shop_name' => 'Lidl',
                'country' => CountryCode::GERMANY->value,
                'postal_code' => '22525',
                'city' => 'Hamburg',
                'street' => 'Kieler Straße',
                'house_number' => '595',
                'display_order' => 2,
            ],

            [
                'shop_name' => 'BAUHAUS',
                'country' => CountryCode::GERMANY->value,
                'postal_code' => '22529',
                'city' => 'Hamburg',
                'street' => 'Alte Kollaustraße',
                'house_number' => '44/46',
                'is_primary' => true,
                'display_order' => 1,
            ],
            [
                'shop_name' => 'Edeka',
                'country' => CountryCode::GERMANY->value,
                'postal_code' => '22529',
                'city' => 'Hamburg',
                'street' => 'Fuhlsbuettler Str.',
                'house_number' => '18',
                'is_primary' => true,
                'display_order' => 1,
            ],
            [
                'shop_name' => 'Fahrschule Altona',
                'country' => CountryCode::GERMANY->value,
                'postal_code' => '22765',
                'city' => 'Hamburg',
                'street' => 'Lobuschstraße',
                'house_number' => '14',
                'is_primary' => true,
                'display_order' => 1,
            ],
            [
                'shop_name' => 'IKEA',
                'country' => CountryCode::GERMANY->value,
                'postal_code' => '22457',
                'city' => 'Hamburg',
                'street' => 'Wunderbrunnen',
                'house_number' => '1',
                'is_primary' => true,
                'display_order' => 1,
            ],
            [
                'shop_name' => 'OBI',
                'country' => CountryCode::GERMANY->value,
                'postal_code' => '22529',
                'city' => 'Hamburg',
                'street' => 'Nedderfeld',
                'house_number' => '100',
                'is_primary' => true,
                'display_order' => 1,
            ],
            [
                'shop_name' => 'A.T.U.',
                'country' => CountryCode::GERMANY->value,
                'postal_code' => '22453',
                'city' => 'Hamburg',
                'street' => 'Kollaustrasse',
                'house_number' => '161',
                'is_primary' => true,
                'display_order' => 1,
            ],
            [
                'shop_name' => 'easyApotheke',
                'country' => CountryCode::GERMANY->value,
                'postal_code' => '22529',
                'city' => 'Hamburg',
                'street' => 'Nedderfeld',
                'house_number' => '70',
                'is_primary' => true,
                'display_order' => 1,
            ],
            [
                'shop_name' => 'Apotheke a.d. Friedenseiche Nikolaus Wendel',
                'country' => CountryCode::GERMANY->value,
                'postal_code' => '20251',
                'city' => 'Hamburg',
                'street' => 'Eppendorfer Marktplatz',
                'house_number' => '2',
                'is_primary' => true,
                'display_order' => 1,
            ],
            [
                'shop_name' => 'ROHLFS BÄCKEREI KONDITOREI GmbH',
                'country' => CountryCode::GERMANY->value,
                'postal_code' => '22299',
                'city' => 'Hamburg',
                'street' => 'Winterhuder Marktplatz',
                'house_number' => '13-15',
                'is_primary' => true,
                'display_order' => 1,
            ],
        ];

        $addresses = collect($addressBlueprint)
            ->map(function (array $address, int $index) use ($shops, $timestamp) {
                $shopId = $shops[$address['shop_name']] ?? null;
                if (!$shopId) {
                    return null;
                }

                return [
                    'shop_id' => $shopId,
                    'country' => $address['country'] ?? CountryCode::GERMANY->value,
                    'postal_code' => $address['postal_code'],
                    'city' => $address['city'],
                    'street' => $address['street'],
                    'house_number' => $address['house_number'],
                    'is_primary' => $address['is_primary'] ?? false,
                    'display_order' => $address['display_order'] ?? ($index + 1),
                    'is_active' => $address['is_active'] ?? true,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            })
            ->filter()
            ->values()
            ->all();

        if (empty($addresses)) {
            return;
        }

        DB::table('shop_addresses')->upsert(
            $addresses,
            ['shop_id', 'postal_code', 'street', 'house_number'],
            ['country', 'city', 'is_primary', 'display_order', 'is_active', 'updated_at']
        );
    }
}
