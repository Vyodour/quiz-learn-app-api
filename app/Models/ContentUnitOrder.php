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
    ];

    public function orderedUnit(): MorphTo
    {
        return $this->morphTo();
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }
}
