<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Lessons extends Model
{
    protected $fillable = [
        'title',
        'content_body',
    ];
    public function orderEntries(): MorphMany
    {
        return $this->morphMany(ContentUnitOrder::class,'ordered_unit');
    }
}
