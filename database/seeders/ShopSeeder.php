<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ShopSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $timestamp = now();

        // Verified shops with physical presence that we actively track
        $shops = [
            ['name' => 'REWE', 'type' => 'in_store', 'country' => 'Germany', 'display_order' => 1],
            ['name' => 'Kaufland', 'type' => 'in_store', 'country' => 'Germany', 'display_order' => 2],
            ['name' => 'Lidl', 'type' => 'in_store', 'country' => 'Germany', 'display_order' => 3],
            ['name' => 'BAUHAUS', 'type' => 'in_store', 'country' => 'Germany', 'display_order' => 4],
            ['name' => 'Edeka', 'type' => 'in_store', 'country' => 'Germany', 'display_order' => 5],
            ['name' => 'Fahrschule Altona', 'type' => 'in_store', 'country' => 'Germany', 'display_order' => 6],
            ['name' => 'IKEA', 'type' => 'in_store', 'country' => 'Germany', 'display_order' => 7],
            ['name' => 'OBI', 'type' => 'in_store', 'country' => 'Germany', 'display_order' => 8],
            ['name' => 'A.T.U.', 'type' => 'in_store', 'country' => 'Germany', 'display_order' => 9],
            ['name' => 'easyApotheke', 'type' => 'in_store', 'country' => 'Germany', 'display_order' => 10],
            ['name' => 'Apotheke a.d. Friedenseiche Nikolaus Wendel', 'type' => 'in_store', 'country' => 'Germany', 'display_order' => 11],
            ['name' => 'ROHLFS BÄCKEREI KONDITOREI GmbH', 'type' => 'in_store', 'country' => 'Germany', 'display_order' => 12],
        ];

        $shops = array_map(function ($shop) use ($timestamp) {
            return array_merge([
                'type' => 'in_store',
                'country' => 'Germany',
                'display_order' => 0,
                'is_active' => true,
            ], $shop, [
                'slug' => Str::slug($shop['name']),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }, $shops);

        DB::table('shops')->insert($shops);
    }
}
