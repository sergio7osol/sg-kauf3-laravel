<?php

namespace App\DTO\Purchase;

/**
 * Purchase Receipt File DTO - represents an attachment file metadata
 */
readonly class PurchaseReceiptFileData
{
    public function __construct(
        public int $id,
        public int $purchaseId,
        public string $originalFilename,
        public string $mimeType,
        public int $size,
        public string $uploadedAt,
        public string $downloadUrl,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'purchaseId' => $this->purchaseId,
            'originalFilename' => $this->originalFilename,
            'mimeType' => $this->mimeType,
            'size' => $this->size,
            'uploadedAt' => $this->uploadedAt,
            'downloadUrl' => $this->downloadUrl,
        ];
    }
}
