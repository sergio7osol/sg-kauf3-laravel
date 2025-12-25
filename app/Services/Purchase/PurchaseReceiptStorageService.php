<?php

namespace App\Services\Purchase;

use App\Models\Purchase;
use App\Models\PurchaseReceiptFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Handles storage operations for purchase receipt attachments.
 * Single Responsibility: file storage, retrieval, and deletion.
 */
class PurchaseReceiptStorageService
{
    private const DISK = 'receipts';
    private const MAX_FILE_SIZE = 3 * 1024 * 1024; // 3 MB
    private const ALLOWED_MIMES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
    ];
    private const MAX_FILES_PER_PURCHASE = 10;

    /**
     * Store multiple attachments for a purchase.
     *
     * @param Purchase $purchase
     * @param UploadedFile[] $files
     * @param int $userId
     * @return PurchaseReceiptFile[]
     */
    public function storeMany(Purchase $purchase, array $files, int $userId): array
    {
        $stored = [];

        foreach ($files as $file) {
            $stored[] = $this->store($purchase, $file, $userId);
        }

        return $stored;
    }

    /**
     * Store a single attachment for a purchase.
     */
    public function store(Purchase $purchase, UploadedFile $file, int $userId): PurchaseReceiptFile
    {
        $originalFilename = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();
        $size = $file->getSize();
        $checksum = hash_file('sha256', $file->getRealPath());

        // Generate unique path: user_id/purchase_id/uuid.ext
        $extension = $file->getClientOriginalExtension() ?: $this->guessExtension($mimeType);
        $path = sprintf(
            '%d/%d/%s.%s',
            $userId,
            $purchase->id,
            Str::uuid(),
            $extension
        );

        // Store file on disk
        Storage::disk(self::DISK)->put($path, file_get_contents($file->getRealPath()));

        // Create database record
        return PurchaseReceiptFile::create([
            'purchase_id' => $purchase->id,
            'user_id' => $userId,
            'disk' => self::DISK,
            'path' => $path,
            'original_filename' => $originalFilename,
            'mime_type' => $mimeType,
            'size' => $size,
            'checksum' => $checksum,
        ]);
    }

    /**
     * Get the file contents for streaming/download.
     */
    public function getContents(PurchaseReceiptFile $attachment): ?string
    {
        if (!Storage::disk($attachment->disk)->exists($attachment->path)) {
            return null;
        }

        return Storage::disk($attachment->disk)->get($attachment->path);
    }

    /**
     * Get the full path to the file.
     */
    public function getFullPath(PurchaseReceiptFile $attachment): ?string
    {
        return Storage::disk($attachment->disk)->path($attachment->path);
    }

    /**
     * Delete an attachment (file + database record).
     */
    public function delete(PurchaseReceiptFile $attachment): bool
    {
        // Delete file from storage
        if (Storage::disk($attachment->disk)->exists($attachment->path)) {
            Storage::disk($attachment->disk)->delete($attachment->path);
        }

        // Soft delete the database record
        return $attachment->delete();
    }

    /**
     * Check if a purchase can accept more attachments.
     */
    public function canAcceptMoreFiles(Purchase $purchase, int $newFilesCount = 1): bool
    {
        $currentCount = $purchase->attachments()->count();
        return ($currentCount + $newFilesCount) <= self::MAX_FILES_PER_PURCHASE;
    }

    /**
     * Get the maximum allowed files per purchase.
     */
    public function getMaxFilesPerPurchase(): int
    {
        return self::MAX_FILES_PER_PURCHASE;
    }

    /**
     * Get allowed MIME types.
     */
    public function getAllowedMimes(): array
    {
        return self::ALLOWED_MIMES;
    }

    /**
     * Get maximum file size in bytes.
     */
    public function getMaxFileSize(): int
    {
        return self::MAX_FILE_SIZE;
    }

    /**
     * Guess file extension from MIME type.
     */
    private function guessExtension(string $mimeType): string
    {
        return match ($mimeType) {
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            default => 'bin',
        };
    }
}
