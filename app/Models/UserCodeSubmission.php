<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCodeSubmission extends Model
{
    protected $fillable = [
        'user_id',
        'challenge_id',
        'is_passed',
        'score',
        'submitted_code',
        'grading_log',
    ];

    protected $casts = [
        'is_passed' => 'boolean',
        'grading_log' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(CodeChallenge::class, 'challenge_id');
    }
}
