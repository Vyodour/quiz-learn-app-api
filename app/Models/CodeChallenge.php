<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CodeChallenge extends Model
{
    const LANG_PYTHON = 'python';
    const LANG_JAVASCRIPT = 'javascript';
    const LANG_PHP = 'php';
    const LANG_JAVA = 'java';

    protected $table = 'code_challenges';

    protected $fillable = [
        'instruction_body',
        'initial_code',
        'language',
        'test_cases',
        'passing_score',
    ];

    protected $casts = [
        'test_cases' => 'array',
        'passing_score' => 'integer',
        'language' => 'string',
    ];

    public function contentUnitOrder(): MorphOne
    {
        return $this->morphOne(ContentUnitOrder::class, 'ordered_unit');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(UserCodeSubmission::class, 'code_challenge_id');
    }
}
