<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCodeSubmission extends Model
{
    protected $table = 'user_code_submissions';

    protected $fillable = [
        'user_id',
        'code_challenge_id',
        'is_passed',
        'score',
        'submitted_code',
        'grading_log',
    ];

    protected $casts = [
        'is_passed' => 'boolean',
        'score' => 'integer',
        'grading_log' => 'array',
    ];

    public function codeChallenge(): BelongsTo
    {
        return $this->belongsTo(CodeChallenge::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
