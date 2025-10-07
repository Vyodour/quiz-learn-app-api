<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

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
        return $this->hasMany(QuizQuestion::class, 'quiz_info_id');
    }

    public function orderEntries(): MorphMany
    {
        return $this->morphMany(ContentUnitOrder::class, 'ordered_unit');
    }
}
