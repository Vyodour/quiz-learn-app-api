<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Lesson extends Model
{
    protected $fillable = [
        'body',
        'video_url',
        'attachment_url',
    ];

    public function contentUnitOrder(): MorphOne
    {
        return $this->morphOne(ContentUnitOrder::class, 'ordered_unit');
    }
}
