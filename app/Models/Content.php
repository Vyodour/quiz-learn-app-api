<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}
