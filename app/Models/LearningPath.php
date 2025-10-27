<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

class LearningPath extends Model
{
    protected $fillable = ['title', 'slug', 'description', 'image_url', 'is_published'];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function modules(): HasMany
    {
        return $this->hasMany(Module::class)->orderBy('order_number');
    }

    public function isCompletedByUser(User $user): bool
    {
        $modules = $this->modules;

        if ($modules->isEmpty()) {
            return true;
        }

        foreach ($modules as $module) {
            if (!$module->isCompletedByUser($user)) {
                return false;
            }
        }

        return true;
    }
}
