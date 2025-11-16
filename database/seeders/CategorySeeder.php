<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Seed the categories table with a curated hierarchy.
     */
    public function run(): void
    {
        $timestamp = now();

        $categoryBlueprint = [
            // Top-level categories
            [
                'name' => 'Groceries',
                'slug' => 'groceries',
                'description' => 'Food, beverages, and everything needed for the kitchen.',
                'icon' => 'shopping-basket',
                'display_order' => 1,
            ],
            [
                'name' => 'Household & Cleaning',
                'slug' => 'household-cleaning',
                'description' => 'Cleaning supplies and household essentials.',
                'icon' => 'spray-bottle',
                'display_order' => 2,
            ],
            [
                'name' => 'Health & Pharmacy',
                'slug' => 'health-pharmacy',
                'description' => 'Over-the-counter medicine and wellness products.',
                'icon' => 'medical-cross',
                'display_order' => 3,
            ],
            [
                'name' => 'Home & DIY',
                'slug' => 'home-diy',
                'description' => 'Home improvement tools, furniture, and décor.',
                'icon' => 'hammer',
                'display_order' => 4,
            ],
            [
                'name' => 'Automotive & Mobility',
                'slug' => 'automotive-mobility',
                'description' => 'Auto parts, maintenance, and related services.',
                'icon' => 'car-wrench',
                'display_order' => 5,
            ],
            [
                'name' => 'Services',
                'slug' => 'services',
                'description' => 'Education, training, and miscellaneous service providers.',
                'icon' => 'briefcase',
                'display_order' => 6,
            ],

            // Groceries children
            [
                'name' => 'Fresh Produce',
                'slug' => 'fresh-produce',
                'description' => 'Fruits, vegetables, and fresh herbs.',
                'icon' => 'apple-whole',
                'display_order' => 1,
                'parent_slug' => 'groceries',
            ],
            [
                'name' => 'Dairy & Eggs',
                'slug' => 'dairy-eggs',
                'description' => 'Milk, cheese, butter, yogurt, and eggs.',
                'icon' => 'cheese',
                'display_order' => 2,
                'parent_slug' => 'groceries',
            ],
            [
                'name' => 'Bakery & Bread',
                'slug' => 'bakery-bread',
                'description' => 'Bread, pastries, and baked sweets.',
                'icon' => 'bread-slice',
                'display_order' => 3,
                'parent_slug' => 'groceries',
            ],
            [
                'name' => 'Meat & Poultry',
                'slug' => 'meat-poultry',
                'description' => 'Fresh and prepared meat or poultry products.',
                'icon' => 'drumstick',
                'display_order' => 4,
                'parent_slug' => 'groceries',
            ],
            [
                'name' => 'Seafood & Deli',
                'slug' => 'seafood-deli',
                'description' => 'Fish, seafood, and deli specialties.',
                'icon' => 'fish',
                'display_order' => 5,
                'parent_slug' => 'groceries',
            ],
            [
                'name' => 'Pantry Staples',
                'slug' => 'pantry-staples',
                'description' => 'Dry goods, canned food, oils, and seasonings.',
                'icon' => 'jar',
                'display_order' => 6,
                'parent_slug' => 'groceries',
            ],
            [
                'name' => 'Beverages',
                'slug' => 'beverages',
                'description' => 'Juices, soft drinks, coffee, and tea.',
                'icon' => 'wine-glass',
                'display_order' => 7,
                'parent_slug' => 'groceries',
            ],
            [
                'name' => 'Snacks & Sweets',
                'slug' => 'snacks-sweets',
                'description' => 'Confectionery, biscuits, and salty snacks.',
                'icon' => 'candy-cane',
                'display_order' => 8,
                'parent_slug' => 'groceries',
            ],

            // Household & Cleaning
            [
                'name' => 'Household Supplies',
                'slug' => 'household-supplies',
                'description' => 'Cleaning agents, detergents, paper goods.',
                'icon' => 'broom',
                'display_order' => 1,
                'parent_slug' => 'household-cleaning',
            ],
            [
                'name' => 'Personal Care',
                'slug' => 'personal-care',
                'description' => 'Shampoos, soaps, and hygiene items.',
                'icon' => 'pump-soap',
                'display_order' => 2,
                'parent_slug' => 'household-cleaning',
            ],

            // Health & Pharmacy
            [
                'name' => 'Pharmacy Essentials',
                'slug' => 'pharmacy-essentials',
                'description' => 'OTC medicine, vitamins, and pharmacy goods.',
                'icon' => 'capsules',
                'display_order' => 1,
                'parent_slug' => 'health-pharmacy',
            ],

            // Home & DIY
            [
                'name' => 'Home Improvement',
                'slug' => 'home-improvement',
                'description' => 'Tools, hardware, and renovation materials.',
                'icon' => 'wrench',
                'display_order' => 1,
                'parent_slug' => 'home-diy',
            ],
            [
                'name' => 'Furniture & Living',
                'slug' => 'furniture-living',
                'description' => 'Furniture, décor, and storage.',
                'icon' => 'couch',
                'display_order' => 2,
                'parent_slug' => 'home-diy',
            ],

            // Automotive & Mobility
            [
                'name' => 'Auto Parts & Service',
                'slug' => 'auto-parts-service',
                'description' => 'Spare parts and workshop services.',
                'icon' => 'car',
                'display_order' => 1,
                'parent_slug' => 'automotive-mobility',
            ],

            // Services
            [
                'name' => 'Driving School',
                'slug' => 'driving-school',
                'description' => 'Lessons, exams, and related fees.',
                'icon' => 'steering-wheel',
                'display_order' => 1,
                'parent_slug' => 'services',
            ],
        ];

        $records = collect($categoryBlueprint)
            ->map(function (array $category) use ($timestamp) {
                return [
                    'name' => $category['name'],
                    'slug' => $category['slug'],
                    'description' => $category['description'] ?? null,
                    'icon' => $category['icon'] ?? null,
                    'display_order' => $category['display_order'] ?? 0,
                    'is_active' => $category['is_active'] ?? true,
                    'parent_id' => null,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            })
            ->all();

        if (! empty($records)) {
            DB::table('categories')->upsert(
                $records,
                ['slug'],
                ['name', 'description', 'icon', 'display_order', 'is_active', 'parent_id', 'updated_at']
            );
        }

        $categories = DB::table('categories')->get(['id', 'slug', 'parent_id'])->keyBy('slug');

        foreach ($categoryBlueprint as $category) {
            $slug = $category['slug'];
            $parentSlug = $category['parent_slug'] ?? null;
            $desiredParentId = $parentSlug ? ($categories[$parentSlug]->id ?? null) : null;
            $current = $categories[$slug] ?? null;

            if (! $current) {
                continue;
            }

            if ($current->parent_id !== $desiredParentId) {
                DB::table('categories')
                    ->where('id', $current->id)
                    ->update([
                        'parent_id' => $desiredParentId,
                        'updated_at' => $timestamp,
                    ]);
            }
        }
    }
}
