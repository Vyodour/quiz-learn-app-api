<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

class Content extends Model
{
    protected $fillable = [
        'module_id',
        'title',
        'slug',
        'order_number'
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function orderedUnits(): HasMany
    {
        return $this->hasMany(ContentUnitOrder::class, 'content_id')->orderBy('order_number');
    }

    public function isCompletedByUser(User $user): bool
    {
        $unitIds = $this->orderedUnits()->pluck('id');

        if ($unitIds->isEmpty()) {
            return true;
        }

        $completedCount = $user->userProgresses()
            ->whereIn('content_unit_order_id', $unitIds)
            ->where('is_completed', true)
            ->count();

        return $completedCount === $unitIds->count();
    }

    public function isPreviousContentCompleted(User $user): bool
    {
        $previousContent = Content::where('module_id', $this->module_id)
            ->where('order_number', $this->order_number - 1)
            ->first();

        if (!$previousContent) {
            return true;
        }

        return $previousContent->isCompletedByUser($user);
    }
    public function isAccessible(User $user): bool
    {
        if (!$this->module->isPreviousModuleCompleted($user)) {
            return false;
        }

        if (!$this->isPreviousContentCompleted($user)) {
            return false;
        }

        return true;
    }
}
