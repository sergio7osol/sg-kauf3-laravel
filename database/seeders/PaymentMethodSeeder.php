<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $timestamp = now();

        $paymentMethods = [
            [
                'name' => 'EC card',
                'slug' => Str::slug('EC card'),
                'category' => 'card',
                'description' => 'Electronic cash / debit card payments.',
                'display_order' => 1,
                'is_active' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'Cash',
                'slug' => Str::slug('Cash'),
                'category' => 'cash',
                'description' => 'Physical cash transactions.',
                'display_order' => 2,
                'is_active' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'N26 card',
                'slug' => Str::slug('N26 card'),
                'category' => 'card',
                'description' => 'N26 bank card payments.',
                'display_order' => 3,
                'is_active' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'PayPal',
                'slug' => Str::slug('PayPal'),
                'category' => 'digital',
                'description' => 'PayPal online payments.',
                'display_order' => 4,
                'is_active' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'Amazon VISA',
                'slug' => Str::slug('Amazon VISA'),
                'category' => 'card',
                'description' => 'Amazon-branded VISA credit card.',
                'display_order' => 5,
                'is_active' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        ];

        DB::table('payment_methods')->insert($paymentMethods);
    }
}
