<?php

namespace App\Console\Commands;

use App\Services\Receipt\ReceiptImportService;
use Illuminate\Console\Command;

/**
 * Test command for full receipt parsing pipeline.
 * Usage: sail artisan receipts:parse resources/PSD/data/some-receipt.png
 */
class ParseReceiptCommand extends Command
{
    protected $signature = 'receipts:parse 
                            {file : Path to the receipt file (PDF or image)}
                            {--json : Output as JSON instead of formatted text}
                            {--debug : Show debug output for each line classification}';

    protected $description = 'Parse a receipt file and show extracted purchase data';

    public function handle(ReceiptImportService $importService): int
    {
        $filePath = $this->argument('file');

        // Resolve relative paths from project root
        if (!str_starts_with($filePath, '/')) {
            $filePath = base_path($filePath);
        }

        $this->info("Parsing receipt: {$filePath}");
        $this->newLine();

        // Collect debug events if --debug is set
        $debugLog = [];
        $debug = $this->option('debug')
            ? function (string $event, array $context = []) use (&$debugLog) {
                $debugLog[] = ['event' => $event, 'context' => $context];
            }
            : null;

        $result = $importService->importFromFile($filePath, $debug);

        if ($this->option('json')) {
            $this->line(json_encode($result->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return $result->success ? Command::SUCCESS : Command::FAILURE;
        }

        if (!$result->success) {
            $this->error("Parsing failed: {$result->error}");
            return Command::FAILURE;
        }

        // Display results
        $this->info("✅ Parsing successful!");
        $this->newLine();

        $this->table(['Field', 'Value'], [
            ['Shop', $result->shopName ?? 'Unknown'],
            ['Shop ID', $result->shopId ?? 'Not matched'],
            ['Address', $result->addressDisplay ?? 'Unknown'],
            ['Address ID', $result->addressId ?? 'Not matched'],
            ['Date', $result->date ?? 'Unknown'],
            ['Time', $result->time ?? 'Unknown'],
            ['Currency', $result->currency],
            ['Subtotal', number_format($result->subtotal, 2) . ' ' . $result->currency],
            ['Total', number_format($result->total, 2) . ' ' . $result->currency],
            ['Items Count', count($result->items)],
            ['Confidence', $result->confidence],
        ]);

        $this->newLine();

        // Warnings
        if (!empty($result->warnings)) {
            $this->warn('⚠️  Warnings:');
            foreach ($result->warnings as $warning) {
                $this->line("  • {$warning}");
            }
            $this->newLine();
        }

        // Line items (first 20)
        $this->info('📦 Line Items (first 20):');
        $this->newLine();

        $itemRows = [];
        foreach (array_slice($result->items, 0, 20) as $i => $item) {
            $itemRows[] = [
                $i + 1,
                mb_substr($item->name, 0, 35),
                $item->quantity,
                $item->unit,
                number_format($item->unitPrice, 2),
                number_format($item->totalPrice, 2),
                $item->isDiscount ? '✓' : '',
                $item->confidence,
            ];
        }

        $this->table(
            ['#', 'Name', 'Qty', 'Unit', 'Unit Price', 'Total', 'Discount', 'Conf.'],
            $itemRows
        );

        if (count($result->items) > 20) {
            $this->line('... and ' . (count($result->items) - 20) . ' more items');
        }

        // Show debug output if requested
        if ($this->option('debug') && !empty($debugLog)) {
            $this->newLine();
            $this->info('🔍 Debug Log:');
            $this->newLine();

            // Group events by type for summary
            $eventCounts = [];
            foreach ($debugLog as $entry) {
                $event = $entry['event'];
                $eventCounts[$event] = ($eventCounts[$event] ?? 0) + 1;
            }

            $this->line('Event Summary:');
            foreach ($eventCounts as $event => $count) {
                $this->line("  {$event}: {$count}");
            }
            $this->newLine();

            // Show all parsed_item events (products only, not discounts)
            $this->line('All parsed items:');
            $itemEvents = array_filter($debugLog, fn($e) => $e['event'] === 'parsed_item');
            foreach ($itemEvents as $entry) {
                $event = $entry['event'];
                $lineNum = $entry['context']['lineNum'] ?? '?';
                $line = $entry['context']['line'] ?? '';
                $line = mb_substr($line, 0, 60);
                $extra = '';
                if (isset($entry['context']['total'])) {
                    $extra = " [total: {$entry['context']['total']}]";
                }
                $this->line("  [{$lineNum}] {$event}: {$line}{$extra}");
            }

            // All discount events
            $this->newLine();
            $this->line('All parsed discounts:');
            $discountEvents = array_filter($debugLog, fn($e) => $e['event'] === 'parsed_discount');
            foreach ($discountEvents as $entry) {
                $lineNum = $entry['context']['lineNum'] ?? '?';
                $line = $entry['context']['line'] ?? '';
                $price = $entry['context']['price'] ?? null;
                $priceText = $price !== null ? ' [amount: ' . number_format($price, 2) . ']' : '';
                $this->line("  [{$lineNum}] {$line}{$priceText}");
            }

            // Also show sum of item totals for verification
            $itemSum = array_reduce($itemEvents, fn($sum, $e) => $sum + ($e['context']['total'] ?? 0), 0);
            $this->newLine();
            $this->line("Sum of parsed item totals: " . number_format($itemSum, 2));

            // Show discount sum
            $discountSum = array_reduce($discountEvents, fn($sum, $e) => $sum + ($e['context']['price'] ?? 0), 0);
            $this->line("Sum of parsed discounts: " . number_format($discountSum, 2));

            // Show Pfand return sum
            $pfandReturnEvents = array_filter($debugLog, fn($e) => $e['event'] === 'parsed_pfand_return');
            $pfandSum = array_reduce($pfandReturnEvents, fn($sum, $e) => $sum + ($e['context']['total'] ?? 0), 0);
            $this->line("Sum of Pfand returns: " . number_format($pfandSum, 2));

            $this->line("Expected subtotal: " . number_format($itemSum + $discountSum + $pfandSum, 2));
        }

        return Command::SUCCESS;
    }
}
