<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $timestamp = now();

        $currencies = [
            [
                'code' => 'EUR',
                'numeric_code' => '978',
                'symbol' => '€',
                'name' => 'Euro',
                'is_active' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'code' => 'RUB',
                'numeric_code' => '643',
                'symbol' => '₽',
                'name' => 'Russian Ruble',
                'is_active' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        ];

        DB::table('currencies')->insert($currencies);
    }
}
