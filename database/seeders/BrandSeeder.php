<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BrandSeeder extends Seeder
{
    /**
     * Seed the brands table with core private labels and partners.
     */
    public function run(): void
    {
        $timestamp = now();

        $brands = [
            [
                'name' => 'REWE Marktplatz',
                'slug' => 'rewe-marktplatz',
                'description' => 'REWE house brand and marketplace partners.',
            ],
            [
                'name' => 'REWE Beste Wahl',
                'slug' => 'rewe-beste-wahl',
                'description' => 'Premium REWE assortment.',
            ],
            [
                'name' => 'ja!',
                'slug' => 'ja',
                'description' => 'REWE discount line comparable to basic everyday needs.',
            ],
            [
                'name' => 'Kaufland K-Classic',
                'slug' => 'kaufland-k-classic',
                'description' => 'Kaufland flagship private label.',
            ],
            [
                'name' => 'K-Bio',
                'slug' => 'k-bio',
                'description' => 'Kaufland organic product line.',
            ],
            [
                'name' => 'Lidl',
                'slug' => 'lidl',
                'description' => 'Lidl umbrella brand for in-house products.',
            ],
            [
                'name' => 'Lidl Deluxe',
                'slug' => 'lidl-deluxe',
                'description' => 'Lidl premium seasonal range.',
            ],
            [
                'name' => 'EDEKA',
                'slug' => 'edeka',
                'description' => 'EDEKA umbrella brand.',
            ],
            [
                'name' => 'EDEKA Gut & Günstig',
                'slug' => 'edeka-gut-guenstig',
                'description' => 'Value line for essential groceries.',
            ],
            // Generic store brand placeholder (for products without explicit manufacturer)
            [
                'name' => 'Store Brand',
                'slug' => 'store-brand',
                'description' => 'Fallback for items that belong to a shop but lack a dedicated product label.',
            ],
        ];

        $records = collect($brands)
            ->map(function (array $brand) use ($timestamp) {
                return [
                    'name' => $brand['name'],
                    'slug' => $brand['slug'],
                    'description' => $brand['description'] ?? null,
                    'logo_url' => $brand['logo_url'] ?? null,
                    'is_active' => $brand['is_active'] ?? true,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            })
            ->all();

        if (! empty($records)) {
            DB::table('brands')->upsert(
                $records,
                ['slug'],
                ['name', 'description', 'logo_url', 'is_active', 'updated_at']
            );
        }
    }
}
