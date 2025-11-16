<?php

namespace Database\Seeders;

use App\Enums\Measure;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Seed the products table with curated catalog entries.
     */
    public function run(): void
    {
        $timestamp = now();

        $categories = DB::table('categories')->pluck('id', 'slug');
        $brands = DB::table('brands')->pluck('id', 'slug');

        if ($categories->isEmpty()) {
            return;
        }

        $products = [
            [
                'name' => 'Banane BIO',
                'slug' => 'banane-bio',
                'category_slug' => 'fresh-produce',
                'brand_slug' => 'k-bio',
                'default_measure' => Measure::KILOGRAM->value,
                'package_size' => 1,
                'package_unit' => 'kg',
                'description' => 'Organic bananas sold by weight.',
            ],
            [
                'name' => 'Clementine Premium',
                'slug' => 'clementine-premium',
                'category_slug' => 'fresh-produce',
                'default_measure' => Measure::KILOGRAM->value,
            ],
            [
                'name' => 'JA! Maasdamer Käse',
                'slug' => 'ja-maasdamer-kaese',
                'category_slug' => 'dairy-eggs',
                'brand_slug' => 'ja',
                'default_measure' => Measure::PIECE->value,
                'package_size' => 400,
                'package_unit' => 'g',
                'description' => 'Sliced Maasdamer cheese 45% fat i.Tr.',
            ],
            [
                'name' => 'K-Classic Hähnchen Unterkeulen',
                'slug' => 'k-classic-haehnchen-unterkeulen',
                'category_slug' => 'meat-poultry',
                'brand_slug' => 'kaufland-k-classic',
                'default_measure' => Measure::KILOGRAM->value,
            ],
            [
                'name' => 'REWE Beste Wahl Räucherlachs',
                'slug' => 'rewe-beste-wahl-raeucherlachs',
                'category_slug' => 'seafood-deli',
                'brand_slug' => 'rewe-beste-wahl',
                'default_measure' => Measure::PIECE->value,
                'package_size' => 200,
                'package_unit' => 'g',
            ],
            [
                'name' => 'REWE Baguette zum Aufbacken',
                'slug' => 'rewe-baguette-aufbacken',
                'category_slug' => 'bakery-bread',
                'brand_slug' => 'rewe-marktplatz',
                'default_measure' => Measure::PIECE->value,
                'package_size' => 2,
                'package_unit' => 'pcs',
            ],
            [
                'name' => 'Lidl Deluxe Santa Glamour',
                'slug' => 'lidl-deluxe-santa-glamour',
                'category_slug' => 'snacks-sweets',
                'brand_slug' => 'lidl-deluxe',
                'default_measure' => Measure::PIECE->value,
            ],
            [
                'name' => 'Store Brand Vitamin C Brausetabletten',
                'slug' => 'store-brand-vitamin-c-brausetabletten',
                'category_slug' => 'pharmacy-essentials',
                'brand_slug' => 'store-brand',
                'default_measure' => Measure::PIECE->value,
                'package_size' => 20,
                'package_unit' => 'tabs',
                'description' => 'Effervescent vitamin tablets for daily supplement.',
            ],
            [
                'name' => 'Standard H7 Glühlampe',
                'slug' => 'standard-h7-gluehlampe',
                'category_slug' => 'auto-parts-service',
                'brand_slug' => 'store-brand',
                'default_measure' => Measure::PIECE->value,
                'description' => 'Replacement headlight bulb for most passenger cars.',
            ],
            [
                'name' => 'FRAKTA Einkaufstasche',
                'slug' => 'frakta-tasche',
                'category_slug' => 'furniture-living',
                'brand_slug' => 'store-brand',
                'default_measure' => Measure::PIECE->value,
            ],
            [
                'name' => 'EDEKA Gut & Günstig Sonnenblumenöl',
                'slug' => 'edeka-gut-guenstig-sonnenblumenoel',
                'category_slug' => 'pantry-staples',
                'brand_slug' => 'edeka-gut-guenstig',
                'default_measure' => Measure::PIECE->value,
                'package_size' => 1,
                'package_unit' => 'l',
            ],
            [
                'name' => 'Store Brand Allzweckreiniger',
                'slug' => 'store-brand-allzweckreiniger',
                'category_slug' => 'household-supplies',
                'brand_slug' => 'store-brand',
                'default_measure' => Measure::PIECE->value,
                'package_size' => 1,
                'package_unit' => 'l',
            ],
            [
                'name' => 'Store Brand Schraubenzieher-Set',
                'slug' => 'store-brand-schraubenzieher-set',
                'category_slug' => 'home-improvement',
                'brand_slug' => 'store-brand',
                'default_measure' => Measure::PIECE->value,
                'package_size' => 6,
                'package_unit' => 'pcs',
            ],
        ];

        $records = collect($products)
            ->map(function (array $product) use ($timestamp, $categories, $brands) {
                $categoryId = $categories[$product['category_slug']] ?? null;
                if (! $categoryId) {
                    return null;
                }

                $brandSlug = $product['brand_slug'] ?? null;

                return [
                    'name' => $product['name'],
                    'slug' => $product['slug'] ?? Str::slug($product['name']),
                    'category_id' => $categoryId,
                    'brand_id' => $brandSlug ? ($brands[$brandSlug] ?? null) : null,
                    'default_measure' => $product['default_measure'] ?? Measure::PIECE->value,
                    'package_size' => $product['package_size'] ?? null,
                    'package_unit' => $product['package_unit'] ?? null,
                    'description' => $product['description'] ?? null,
                    'barcode' => $product['barcode'] ?? null,
                    'is_active' => $product['is_active'] ?? true,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            })
            ->filter()
            ->values()
            ->all();

        if (! empty($records)) {
            DB::table('products')->upsert(
                $records,
                ['slug'],
                ['name', 'category_id', 'brand_id', 'default_measure', 'package_size', 'package_unit', 'description', 'barcode', 'is_active', 'updated_at']
            );
        }
    }
}
