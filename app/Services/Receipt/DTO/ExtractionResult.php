<?php

namespace App\Services\Receipt\DTO;

/**
 * Result of text extraction from a receipt file (PDF/image).
 */
readonly class ExtractionResult
{
    public function __construct(
        public bool $success,
        public string $text,
        public string $fileType,
        public string $extractionMethod,
        public ?string $error = null,
    ) {}

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'text' => $this->text,
            'fileType' => $this->fileType,
            'extractionMethod' => $this->extractionMethod,
            'error' => $this->error,
        ];
    }

    public static function failure(string $error, string $fileType = 'unknown'): self
    {
        return new self(
            success: false,
            text: '',
            fileType: $fileType,
            extractionMethod: 'none',
            error: $error,
        );
    }
}
