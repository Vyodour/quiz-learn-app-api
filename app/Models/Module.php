<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}
