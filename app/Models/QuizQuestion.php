<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizQuestion extends Model
{
    use HasFactory;

    protected $table = 'quiz_questions';

    protected $fillable = [
        'quiz_information_id',
        'question_text',
        'options',
        'correct_option_index',
    ];

    protected $casts = [
        'options' => 'array',
        'correct_option_index' => 'integer',
    ];

    public function quizInformation(): BelongsTo
    {
        return $this->belongsTo(QuizInformation::class, 'quiz_information_id');
    }
}
