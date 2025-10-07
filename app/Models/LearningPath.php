<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningPath extends Model
{
    // Menambahkan field yang digunakan oleh resource (slug, image_url, is_published)
    protected $fillable = ['title', 'slug', 'description', 'image_url', 'is_published'];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function modules(): HasMany
    {
        // Secara default, urutkan Modul berdasarkan 'order_number'
        return $this->hasMany(Module::class)->orderBy('order_number');
    }
}
