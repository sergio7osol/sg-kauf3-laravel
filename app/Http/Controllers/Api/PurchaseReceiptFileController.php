<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\PurchaseReceiptFile;
use App\Services\Purchase\PurchaseReceiptStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class PurchaseReceiptFileController extends Controller
{
    public function __construct(
        private readonly PurchaseReceiptStorageService $storageService
    ) {}

    /**
     * Download a receipt attachment.
     * Returns 404 if user doesn't own the attachment (security through obscurity).
     */
    public function download(Request $request, Purchase $purchase, PurchaseReceiptFile $attachment): Response|JsonResponse
    {
        // Ensure purchase belongs to authenticated user
        if ($purchase->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Purchase not found.',
            ], 404);
        }

        // Ensure attachment belongs to this purchase
        if ($attachment->purchase_id !== $purchase->id) {
            return response()->json([
                'message' => 'Attachment not found.',
            ], 404);
        }

        // Authorize via policy
        if (!Gate::allows('view', $attachment)) {
            return response()->json([
                'message' => 'Attachment not found.',
            ], 404);
        }

        // Get file contents
        $contents = $this->storageService->getContents($attachment);

        if ($contents === null) {
            return response()->json([
                'message' => 'File not found on disk.',
            ], 404);
        }

        // Stream file download
        return response($contents, 200)
            ->header('Content-Type', $attachment->mime_type)
            ->header('Content-Disposition', 'attachment; filename="' . $attachment->original_filename . '"')
            ->header('Content-Length', $attachment->size);
    }

    /**
     * Delete a receipt attachment.
     */
    public function destroy(Request $request, Purchase $purchase, PurchaseReceiptFile $attachment): JsonResponse
    {
        // Ensure purchase belongs to authenticated user
        if ($purchase->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Purchase not found.',
            ], 404);
        }

        // Ensure attachment belongs to this purchase
        if ($attachment->purchase_id !== $purchase->id) {
            return response()->json([
                'message' => 'Attachment not found.',
            ], 404);
        }

        // Authorize via policy
        if (!Gate::allows('delete', $attachment)) {
            return response()->json([
                'message' => 'Attachment not found.',
            ], 404);
        }

        // Delete file and record
        $this->storageService->delete($attachment);

        return response()->json([
            'message' => 'Attachment deleted successfully.',
        ]);
    }
}
