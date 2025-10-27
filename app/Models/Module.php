<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use App\Models\User;
use App\Models\Content;

class Module extends Model
{
    protected $fillable = [
        'learning_path_id',
        'title',
        'slug',
        'description',
        'duration',
        'level',
        'rating',
        'order_number'
    ];

    public function learningPath(): BelongsTo
    {
        return $this->belongsTo(LearningPath::class);
    }

    public function contents(): HasMany
    {
        return $this->hasMany(Content::class)->orderBy('order_number');
    }

    public function contentUnitOrders(): HasManyThrough
    {
        return $this->hasManyThrough(
            ContentUnitOrder::class,
            Content::class,
            'module_id',
            'content_id',
            'id',
            'id'
        );
    }

    public function isCompletedByUser(User $user): bool
    {
        $contents = $this->contents;

        if ($contents->isEmpty()) {
            return true;
        }

        foreach ($contents as $content) {
            if (!$content->isCompletedByUser($user)) {
                return false;
            }
        }

        return true;
    }

    public function isPreviousModuleCompleted(User $user): bool
    {
        $previousModule = Module::where('learning_path_id', $this->learning_path_id)
            ->where('order_number', $this->order_number - 1)
            ->first();

        if (!$previousModule) {
            return true;
        }
        return $previousModule->isCompletedByUser($user);
    }
}
