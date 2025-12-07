<?php

namespace App\Console\Commands;

use App\Services\Receipt\ReceiptTextExtractor;
use Illuminate\Console\Command;

/**
 * Test command for receipt text extraction.
 * Usage: sail artisan receipts:extract resources/PSD/data/some-receipt.pdf
 */
class ExtractReceiptText extends Command
{
    protected $signature = 'receipts:extract 
                            {file : Path to the receipt file (PDF or image)}
                            {--raw : Output raw text without formatting}';

    protected $description = 'Extract text from a receipt file (PDF or image) for testing';

    public function handle(ReceiptTextExtractor $extractor): int
    {
        $filePath = $this->argument('file');

        // Resolve relative paths from project root
        if (!str_starts_with($filePath, '/')) {
            $filePath = base_path($filePath);
        }

        $this->info("Extracting text from: {$filePath}");
        $this->newLine();

        $result = $extractor->extract($filePath);

        if (!$result->success) {
            $this->error("Extraction failed: {$result->error}");
            return Command::FAILURE;
        }

        $this->info("✅ Extraction successful!");
        $this->line("File type: {$result->fileType}");
        $this->line("Method: {$result->extractionMethod}");
        $this->line("Text length: " . strlen($result->text) . " characters");
        $this->newLine();

        if ($this->option('raw')) {
            $this->line($result->text);
        } else {
            $this->info('--- Extracted Text (first 2000 chars) ---');
            $this->newLine();
            $preview = substr($result->text, 0, 2000);
            $this->line($preview);
            if (strlen($result->text) > 2000) {
                $this->newLine();
                $this->warn('... (truncated, use --raw for full output)');
            }
        }

        return Command::SUCCESS;
    }
}
