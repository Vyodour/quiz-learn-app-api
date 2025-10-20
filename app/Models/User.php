<?php

namespace App\Models;

use App\Models\Subscription;
use Illuminate\Support\Carbon;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function subscriptions()
{
    return $this->hasMany(Subscription::class);
}
public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
public function hasActiveSubscription(): bool
{
    return $this->subscriptions()
        ->where('status', 'active')
        ->where('ends_at', '>', now())
        ->exists();
}
public function getActiveSubscription(): ?Subscription
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->where('ends_at', '>', Carbon::now())
            ->first();
    }

public function userProgresses(): HasMany
    {
        return $this->hasMany(UserUnitProgress::class);
    }

}
