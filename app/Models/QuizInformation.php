<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class QuizInformation extends Model
{
    protected $table = 'quizzes_information';

    protected $fillable = [
        'name',
        'passing_score',
        'time_limit',
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class, 'quiz_information_id');
    }

    public function contentUnitOrder(): MorphOne
    {
        return $this->morphOne(ContentUnitOrder::class, 'ordered_unit');
    }
}
