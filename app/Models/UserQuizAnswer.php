<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserQuizAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'quiz_question_id',
        'submitted_option_index',
        'is_correct',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'submitted_option_index' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class, 'quiz_question_id');
    }
}
