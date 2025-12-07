<?php

namespace App\Console\Commands;

use App\Enums\CountryCode;
use App\Enums\PurchaseChannel;
use App\Models\Purchase;
use App\Models\PurchaseLine;
use App\Models\Shop;
use App\Models\ShopAddress;
use App\Models\User;
use App\Models\UserPaymentMethod;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportLegacyData extends Command
{
    protected $signature = 'import:legacy-data 
                            {--user= : User ID to assign purchases and payment methods to (required)}
                            {--dry-run : Preview import without database changes}
                            {--path= : Custom path to JSON files directory}
                            {--file= : Single JSON file to import (overrides --path)}';

    protected $description = 'Import legacy purchase data from old project JSON files';

    private array $stats = [
        'files' => 0,
        'purchases' => 0,
        'lines' => 0,
        'shops_created' => 0,
        'shops_existing' => 0,
        'addresses_created' => 0,
        'addresses_existing' => 0,
        'payment_methods_created' => 0,
        'payment_methods_existing' => 0,
        'skipped_duplicates' => 0,
    ];

    public function handle(): int
    {
        $userId = $this->option('user');
        $dryRun = $this->option('dry-run');
        $file = $this->option('file');
        $path = $this->option('path') ?? resource_path('PSD/OLD-server-project/data');

        // Validate user
        if (!$userId) {
            $this->error('The --user option is required. Example: --user=2');
            return Command::FAILURE;
        }

        $user = User::find($userId);
        if (!$user) {
            $this->error("User with ID {$userId} not found.");
            return Command::FAILURE;
        }

        // Resolve files (single file overrides path)
        if ($file) {
            if (!is_file($file)) {
                $this->error("File not found: {$file}");
                return Command::FAILURE;
            }
            $files = [$file];
            $this->info($dryRun ? '🔍 DRY RUN MODE - No changes will be made' : '🚀 Starting import...');
            $this->info("Source file: {$file}");
        } else {
            if (!is_dir($path)) {
                $this->error("Directory not found: {$path}");
                return Command::FAILURE;
            }
            $files = glob($path . '/*.json');
            $this->info($dryRun ? '🔍 DRY RUN MODE - No changes will be made' : '🚀 Starting import...');
            $this->info("Source: {$path}");
        }
        $this->info("Target user: {$user->name} (ID: {$user->id})");
        $this->newLine();

        if (empty($files)) {
            $this->warn('No JSON files found in the specified directory.');
            return Command::SUCCESS;
        }

        $this->stats['files'] = count($files);
        $this->info("Found {$this->stats['files']} JSON files.");

        // Parse all purchase records
        $allPurchases = $this->loadAllPurchases($files);
        $this->info("Loaded " . count($allPurchases) . " purchase records.");
        $this->newLine();

        if ($dryRun) {
            $this->previewImport($allPurchases, $user);
            return Command::SUCCESS;
        }

        // Execute import in transaction
        try {
            DB::transaction(function () use ($allPurchases, $user) {
                $this->importShops($allPurchases);
                $this->importAddresses($allPurchases);
                $this->importPaymentMethods($allPurchases, $user);
                $this->importPurchases($allPurchases, $user);
            });
        } catch (\Exception $e) {
            $this->error('Import failed: ' . $e->getMessage());
            $this->error('All changes have been rolled back.');
            return Command::FAILURE;
        }

        $this->displaySummary();
        return Command::SUCCESS;
    }

    private function loadAllPurchases(array $files): array
    {
        $purchases = [];

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $records = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->warn("Skipping invalid JSON: " . basename($file));
                continue;
            }

            if (!is_array($records)) {
                $records = [$records];
            }

            foreach ($records as $record) {
                $record['_source_file'] = basename($file);
                $purchases[] = $record;
            }
        }

        return $purchases;
    }

    private function previewImport(array $allPurchases, User $user): void
    {
        $this->info('📊 PREVIEW SUMMARY');
        $this->newLine();

        // Unique shops
        $uniqueShops = collect($allPurchases)->pluck('shopName')->filter()->unique()->values();
        $this->info("Shops to create/check: {$uniqueShops->count()}");
        $this->line('  ' . $uniqueShops->take(10)->implode(', ') . ($uniqueShops->count() > 10 ? '...' : ''));

        // Unique addresses
        $uniqueAddresses = $this->extractUniqueAddresses($allPurchases);
        $this->info("Addresses to create/check: " . count($uniqueAddresses));

        // Unique payment methods
        $uniquePaymentMethods = collect($allPurchases)
            ->pluck('payMethod')
            ->filter()
            ->map(fn($m) => $this->normalizePayMethod($m))
            ->unique()
            ->values();
        $this->info("Payment methods to create/check: {$uniquePaymentMethods->count()}");
        $this->line('  ' . $uniquePaymentMethods->implode(', '));

        // Purchases
        $this->info("Purchases to import: " . count($allPurchases));

        // Total line items
        $totalLines = collect($allPurchases)->sum(fn($p) => count($p['products'] ?? []));
        $this->info("Line items to import: {$totalLines}");

        $this->newLine();
        $this->info('Run without --dry-run to execute the import.');
    }

    private function importShops(array $allPurchases): void
    {
        $this->info('Creating shops...');

        $uniqueShops = collect($allPurchases)->pluck('shopName')->filter()->unique();

        $bar = $this->output->createProgressBar($uniqueShops->count());
        $bar->start();

        foreach ($uniqueShops as $shopName) {
            $existing = Shop::where('name', $shopName)->first();

            if ($existing) {
                $this->stats['shops_existing']++;
            } else {
                $type = $this->determineShopType($shopName);
                $country = $this->determineShopCountry($shopName);

                Shop::create([
                    'name' => $shopName,
                    'slug' => Str::slug($shopName),
                    'type' => $type,
                    'country' => $country,
                    'display_order' => 0,
                    'is_active' => true,
                ]);
                $this->stats['shops_created']++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function importAddresses(array $allPurchases): void
    {
        $this->info('Creating shop addresses...');

        $uniqueAddresses = $this->extractUniqueAddresses($allPurchases);

        $bar = $this->output->createProgressBar(count($uniqueAddresses));
        $bar->start();

        foreach ($uniqueAddresses as $item) {
            $shop = Shop::where('name', $item['shopName'])->first();
            if (!$shop) {
                $bar->advance();
                continue;
            }

            $address = $item['address'];
            $country = $this->normalizeCountry($address['country'] ?? 'Germany');

            $existing = ShopAddress::where('shop_id', $shop->id)
                ->where('postal_code', $address['index'] ?? '')
                ->where('street', $address['street'] ?? '')
                ->where('house_number', $address['houseNumber'] ?? '')
                ->first();

            if ($existing) {
                $this->stats['addresses_existing']++;
            } else {
                ShopAddress::create([
                    'shop_id' => $shop->id,
                    'country' => $country,
                    'postal_code' => $address['index'] ?? '',
                    'city' => $address['city'] ?? '',
                    'street' => $address['street'] ?? '',
                    'house_number' => $address['houseNumber'] ?? '',
                    'is_primary' => false,
                    'display_order' => 0,
                    'is_active' => true,
                ]);
                $this->stats['addresses_created']++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function importPaymentMethods(array $allPurchases, User $user): void
    {
        $this->info('Creating payment methods...');

        $uniqueMethods = collect($allPurchases)
            ->pluck('payMethod')
            ->filter()
            ->map(fn($m) => $this->normalizePayMethod($m))
            ->unique();

        $bar = $this->output->createProgressBar($uniqueMethods->count());
        $bar->start();

        foreach ($uniqueMethods as $methodName) {
            $existing = UserPaymentMethod::where('user_id', $user->id)
                ->where('name', $methodName)
                ->first();

            if ($existing) {
                $this->stats['payment_methods_existing']++;
            } else {
                UserPaymentMethod::create([
                    'user_id' => $user->id,
                    'name' => $methodName,
                    'notes' => 'Imported from legacy data',
                    'is_active' => true,
                ]);
                $this->stats['payment_methods_created']++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function importPurchases(array $allPurchases, User $user): void
    {
        $this->info('Creating purchases...');

        $bar = $this->output->createProgressBar(count($allPurchases));
        $bar->start();

        foreach ($allPurchases as $record) {
            $shop = Shop::where('name', $record['shopName'])->first();
            if (!$shop) {
                $bar->advance();
                continue;
            }

            $address = $record['address'] ?? [];
            $shopAddress = ShopAddress::where('shop_id', $shop->id)
                ->where('postal_code', $address['index'] ?? '')
                ->where('street', $address['street'] ?? '')
                ->first();

            if (!$shopAddress) {
                // Fallback to any address for this shop
                $shopAddress = ShopAddress::where('shop_id', $shop->id)->first();
            }

            if (!$shopAddress) {
                $bar->advance();
                continue;
            }

            $paymentMethod = null;
            if (!empty($record['payMethod'])) {
                $normalizedMethod = $this->normalizePayMethod($record['payMethod']);
                $paymentMethod = UserPaymentMethod::where('user_id', $user->id)
                    ->where('name', $normalizedMethod)
                    ->first();
            }

            $purchaseDate = $this->convertDate($record['date']);
            $currency = $record['currency'] ?? 'EUR';
            $time = $record['time'] ?? '';

            // Check for duplicate purchase (same user, shop, date, time)
            // Only check if we have a time to match against
            if (!empty($time)) {
                $isDuplicate = Purchase::where('user_id', $user->id)
                    ->where('shop_id', $shop->id)
                    ->where('purchase_date', $purchaseDate)
                    ->where('notes', 'LIKE', "%Purchase time: {$time}%")
                    ->exists();

                if ($isDuplicate) {
                    $this->stats['skipped_duplicates']++;
                    $bar->advance();
                    continue;
                }
            }

            // Build notes including time if present
            $notes = null;
            if (!empty($record['time'])) {
                $notes = "Purchase time: {$record['time']}";
            }

            $purchase = Purchase::create([
                'user_id' => $user->id,
                'shop_id' => $shop->id,
                'shop_address_id' => $shopAddress->id,
                'user_payment_method_id' => $paymentMethod?->id,
                'purchase_date' => $purchaseDate,
                'currency' => $currency,
                'status' => 'confirmed',
                'notes' => $notes,
                'receipt_number' => null,
                'subtotal' => 0,
                'tax_amount' => 0,
                'total_amount' => 0,
            ]);

            $this->stats['purchases']++;

            // Create line items
            $lineNumber = 1;
            $totalAmount = 0;

            foreach ($record['products'] ?? [] as $product) {
                $rawPrice = (float) ($product['price'] ?? 0);
                $quantity = (float) ($product['weightAmount'] ?? 1);
                $discountPercent = $this->parseDiscount($product['discount'] ?? 0);
                
                // Handle negative prices (deposit returns like "Leergut")
                $isRefund = $rawPrice < 0;
                $unitPrice = (int) round(abs($rawPrice) * 100);
                
                $discountAmount = (int) round($quantity * $unitPrice * $discountPercent / 100);
                $lineAmount = (int) round($quantity * $unitPrice * (1 - $discountPercent / 100));
                
                // For refunds, negate the line amount for total calculation
                // but store positive values in DB (schema constraint)
                $effectiveLineAmount = $isRefund ? -$lineAmount : $lineAmount;
                
                // Build notes - include refund marker and original description
                $lineNotes = $product['description'] ?? null;
                if ($isRefund) {
                    $lineNotes = '[REFUND/DEPOSIT RETURN] ' . ($lineNotes ?? '');
                }

                PurchaseLine::create([
                    'purchase_id' => $purchase->id,
                    'line_number' => $lineNumber++,
                    'product_id' => null,
                    'description' => $product['name'] ?? 'Unknown item',
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'tax_rate' => 0,
                    'tax_amount' => 0,
                    'discount_percent' => $discountPercent,
                    'discount_amount' => $discountAmount,
                    'line_amount' => $lineAmount,
                    'notes' => $lineNotes,
                ]);

                $totalAmount += $effectiveLineAmount;
                $this->stats['lines']++;
            }

            // Update purchase totals (ensure non-negative for unsigned columns)
            $finalTotal = max(0, $totalAmount);
            $purchase->update([
                'subtotal' => $finalTotal,
                'tax_amount' => 0,
                'total_amount' => $finalTotal,
            ]);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function extractUniqueAddresses(array $allPurchases): array
    {
        $seen = [];
        $unique = [];

        foreach ($allPurchases as $purchase) {
            $shopName = $purchase['shopName'] ?? '';
            $address = $purchase['address'] ?? [];

            $key = $shopName . '|' . json_encode($address);

            if (!isset($seen[$key]) && !empty($shopName)) {
                $seen[$key] = true;
                $unique[] = [
                    'shopName' => $shopName,
                    'address' => $address,
                ];
            }
        }

        return $unique;
    }

    private function convertDate(string $date): string
    {
        // "05.02.2022" → "2022-02-05"
        $parts = explode('.', $date);
        if (count($parts) !== 3) {
            return now()->toDateString();
        }
        return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
    }

    private function parseDiscount($discount): float
    {
        if (is_numeric($discount)) {
            return (float) $discount;
        }
        if (is_string($discount) && str_contains($discount, '%')) {
            return (float) str_replace('%', '', $discount);
        }
        return 0;
    }

    private function normalizePayMethod(string $method): string
    {
        $normalized = [
            'dkb card' => 'DKB Card',
            'ec card' => 'EC Card',
            'amazon visa' => 'Amazon VISA',
            'n26 card' => 'N26 Card',
            'paypal' => 'PayPal',
            'cash' => 'Cash',
        ];

        return $normalized[strtolower(trim($method))] ?? $method;
    }

    private function normalizeCountry(string $country): string
    {
        $map = [
            'germany' => CountryCode::GERMANY->value,
            'deutschland' => CountryCode::GERMANY->value,
            'russia' => CountryCode::RUSSIA->value,
            'russland' => CountryCode::RUSSIA->value,
        ];

        return $map[strtolower(trim($country))] ?? CountryCode::GERMANY->value;
    }

    private function determineShopType(string $shopName): string
    {
        $onlineShops = ['Amazon.de', 'Netflix.com', 'Netflix', 'About you', 'Innovativelanguage.com', 'Mango'];
        $hybridShops = ['IKEA'];

        if (in_array($shopName, $onlineShops)) {
            return PurchaseChannel::ONLINE->value;
        }
        if (in_array($shopName, $hybridShops)) {
            return PurchaseChannel::HYBRID->value;
        }
        return PurchaseChannel::IN_STORE->value;
    }

    private function determineShopCountry(string $shopName): string
    {
        // All shops default to Germany for now
        return CountryCode::GERMANY->value;
    }

    private function displaySummary(): void
    {
        $this->newLine();
        $this->info('✅ Import completed successfully!');
        $this->newLine();

        $this->table(
            ['Entity', 'Created', 'Existing', 'Total'],
            [
                ['Shops', $this->stats['shops_created'], $this->stats['shops_existing'], $this->stats['shops_created'] + $this->stats['shops_existing']],
                ['Addresses', $this->stats['addresses_created'], $this->stats['addresses_existing'], $this->stats['addresses_created'] + $this->stats['addresses_existing']],
                ['Payment Methods', $this->stats['payment_methods_created'], $this->stats['payment_methods_existing'], $this->stats['payment_methods_created'] + $this->stats['payment_methods_existing']],
                ['Purchases', $this->stats['purchases'], $this->stats['skipped_duplicates'] . ' skipped', $this->stats['purchases']],
                ['Line Items', $this->stats['lines'], '-', $this->stats['lines']],
            ]
        );

        $this->newLine();
        $this->info("Processed {$this->stats['files']} JSON files.");
    }
}
