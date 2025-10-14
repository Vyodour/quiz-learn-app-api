<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserUnitProgress extends Model
{
    use HasFactory;

    protected $table = 'user_unit_progress';

    protected $fillable = [
        'user_id',
        'content_unit_order_id',
        'is_completed',
        'completed_at',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function unitOrder(): BelongsTo
    {
        return $this->belongsTo(ContentUnitOrder::class, 'content_unit_order_id');
    }
}
