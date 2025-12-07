<?php

namespace App\Services\Receipt;

use App\Services\Receipt\DTO\ExtractionResult;
use Spatie\PdfToText\Pdf;
use thiagoalessio\TesseractOCR\TesseractOCR;

/**
 * Extracts text from receipt files (PDF or images).
 * 
 * Requires system dependencies:
 * - poppler-utils (for pdftotext)
 * - tesseract-ocr, tesseract-ocr-deu (for OCR)
 */
class ReceiptTextExtractor
{
    private const SUPPORTED_IMAGE_TYPES = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/webp'];
    private const SUPPORTED_PDF_TYPES = ['application/pdf'];

    /**
     * Extract text from a receipt file.
     *
     * @param string $filePath Absolute path to the file
     * @return ExtractionResult
     */
    public function extract(string $filePath): ExtractionResult
    {
        if (!file_exists($filePath)) {
            return ExtractionResult::failure("File not found: {$filePath}");
        }

        $mimeType = $this->detectMimeType($filePath);

        if (in_array($mimeType, self::SUPPORTED_PDF_TYPES, true)) {
            return $this->extractFromPdf($filePath);
        }

        if (in_array($mimeType, self::SUPPORTED_IMAGE_TYPES, true)) {
            return $this->extractFromImage($filePath, $mimeType);
        }

        return ExtractionResult::failure(
            "Unsupported file type: {$mimeType}. Supported: PDF, PNG, JPG, GIF, WEBP.",
            $mimeType
        );
    }

    /**
     * Extract text from PDF using pdftotext (poppler-utils).
     */
    private function extractFromPdf(string $filePath): ExtractionResult
    {
        try {
            $text = Pdf::getText($filePath);
            $text = $this->normalizeText($text);

            return new ExtractionResult(
                success: true,
                text: $text,
                fileType: 'application/pdf',
                extractionMethod: 'pdftotext',
            );
        } catch (\Exception $e) {
            return ExtractionResult::failure(
                "PDF extraction failed: " . $e->getMessage(),
                'application/pdf'
            );
        }
    }

    /**
     * Extract text from image using Tesseract OCR.
     */
    private function extractFromImage(string $filePath, string $mimeType): ExtractionResult
    {
        try {
            $ocr = new TesseractOCR($filePath);
            
            // Use German + English for best results with German receipts
            $ocr->lang('deu', 'eng');
            
            // Optimize for receipt-like documents (single column text)
            $ocr->psm(6); // Assume uniform block of text
            
            $text = $ocr->run();
            $text = $this->normalizeText($text);

            return new ExtractionResult(
                success: true,
                text: $text,
                fileType: $mimeType,
                extractionMethod: 'tesseract-ocr',
            );
        } catch (\Exception $e) {
            return ExtractionResult::failure(
                "OCR extraction failed: " . $e->getMessage(),
                $mimeType
            );
        }
    }

    /**
     * Detect MIME type of a file.
     */
    private function detectMimeType(string $filePath): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        return $mimeType ?: 'unknown';
    }

    /**
     * Normalize extracted text (trim, fix encoding, collapse whitespace).
     */
    private function normalizeText(string $text): string
    {
        // Ensure UTF-8
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'auto');
        }

        // Normalize line endings
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // Trim each line
        $lines = explode("\n", $text);
        $lines = array_map('trim', $lines);

        // Remove completely empty lines at start/end, keep internal structure
        $text = implode("\n", $lines);
        $text = trim($text);

        return $text;
    }
}
