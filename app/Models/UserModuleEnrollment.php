<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\CalculatesModuleProgress;

class UserModuleEnrollment extends Model
{
    use HasFactory, CalculatesModuleProgress;

    protected $fillable = [
        'user_id',
        'module_id',
        'start_date',
        'completion_date',
        'progress_percentage',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'completion_date' => 'datetime',
        'progress_percentage' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function module()
    {
        return $this->belongsTo(Module::class);
    }
}
