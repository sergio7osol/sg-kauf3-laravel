<?php

namespace App\Models;

use App\DTO\LabelData;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Label extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
    ];

    /**
     * Get the user that owns the label.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the purchases that have this label.
     */
    public function purchases(): BelongsToMany
    {
        return $this->belongsToMany(Purchase::class, 'label_purchase')
            ->withPivot('created_at');
    }

    /**
     * Convert model to DTO for API responses.
     */
    public function toData(): LabelData
    {
        return new LabelData(
            id: $this->id,
            userId: $this->user_id,
            name: $this->name,
            description: $this->description,
            createdAt: $this->created_at->toIso8601String(),
        );
    }
}
