<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentUnitOrder extends Model
{
    protected $table = 'content_unit_order';

    protected $fillable = [
        'content_id',
        'order_number',
        'title',
        'ordered_unit_type',
        'ordered_unit_id',
        'is_premium',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
    ];

    public function orderedUnit(): MorphTo
    {
        return $this->morphTo();
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    public function userProgresses(): HasMany
    {
        return $this->hasMany(UserUnitProgress::class);
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
        if ($this->order_number === 1) {
            return true;
        }

        $previousUnit = ContentUnitOrder::where('content_id', $this->content_id)
            ->where('order_number', $this->order_number - 1)
            ->first();

        if (!$previousUnit) {
            return true;
        }

        $progress = UserUnitProgress::where('user_id', $user->id)
            ->where('content_unit_order_id', $previousUnit->id)
            ->first();

        return (bool)($progress && $progress->is_completed);
    }
}
