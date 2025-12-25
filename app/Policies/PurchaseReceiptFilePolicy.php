<?php

namespace App\Policies;

use App\Models\PurchaseReceiptFile;
use App\Models\User;

/**
 * Authorization policy for purchase receipt file operations.
 * Only the user who uploaded the file can view/delete it.
 */
class PurchaseReceiptFilePolicy
{
    /**
     * Determine if the user can view/download the attachment.
     */
    public function view(User $user, PurchaseReceiptFile $attachment): bool
    {
        return $user->id === $attachment->user_id;
    }

    /**
     * Determine if the user can delete the attachment.
     */
    public function delete(User $user, PurchaseReceiptFile $attachment): bool
    {
        return $user->id === $attachment->user_id;
    }
}
