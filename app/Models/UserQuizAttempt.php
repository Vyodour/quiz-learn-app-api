<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserQuizAttempt extends Model
{
    use HasFactory;

    protected $table = 'user_quiz_attempts';

    protected $fillable = [
        'user_id',
        'quiz_information_id',
        'score',
        'is_passed',
        'submitted_answers',
    ];

    protected $casts = [
        'is_passed' => 'boolean',
        'score' => 'integer',
        'submitted_answers' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quizInformation(): BelongsTo
    {
        return $this->belongsTo(QuizInformation::class);
    }
}
