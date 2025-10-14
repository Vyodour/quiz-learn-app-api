<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ContentUnitOrder extends Model
{
    protected $table = 'content_unit_order';

    protected $fillable = [
        'content_id',
        'order_number',
        'is_completed',
        'ordered_unit_type',
        'ordered_unit_id',
        'is_premium',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'is_premium' => 'boolean',
    ];

    public function orderedUnit(): MorphTo
    {
        return $this->morphTo();
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    public function requiresSubscription(): bool
    {
        return $this->is_premium;
    }

    public function canBeAccessedByUser(User $user): bool
{
    if ($this->is_premium) {
        return $user->hasActiveSubscription();
    }
    return true;
}

public function isPreviousUnitCompleted(User $user): bool
{
    $previousOrderUnit = ContentUnitOrder::where('content_id', $this->content_id)
        ->where('order_number', $this->order_number - 1)
        ->first();

    if (!$previousOrderUnit) {
        return true;
    }
    $progress = $user->unitProgresses()
                         ->where('content_unit_order_id', $previousOrderUnit->id)
                         ->first();

        return $progress && $progress->is_completed;
}
}
