<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    // Semua field yang akan diisi secara massal harus didaftarkan di sini.
    // 'title' dan 'slug' ditambahkan sesuai migrasi dan resource terbaru.
    protected $fillable = [
        'learning_path_id',
        'title',          // Mengganti 'name'
        'slug',           // Field baru
        'description',
        'duration',
        'level',
        'rating',
        'order_number'
    ];

    public function learningPath(): BelongsTo
    {
        return $this->belongsTo(LearningPath::class);
    }

    public function contents(): HasMany
    {
        // Relasi contents diurutkan berdasarkan order_number
        return $this->hasMany(Content::class)->orderBy('order_number');
    }
}
