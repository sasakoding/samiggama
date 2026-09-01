<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'status', 'last_login_at', 'last_login_ip'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function articles()
    {
        return $this->hasMany(Article::class);
    }

    public function isAdmin(): bool
    {
        $role = strtolower(trim($this->role ?? ''));
        // Super Administrator, Admin, etc.
        return empty($role) || str_contains($role, 'admin') || !str_contains($role, 'bendahara');
    }

    public function isBendahara(): bool
    {
        $role = strtolower(trim($this->role ?? ''));
        return str_contains($role, 'bendahara');
    }

    public function getRoleDisplayNameAttribute(): string
    {
        if ($this->isBendahara()) {
            return 'Bendahara';
        }
        return 'Admin';
    }
}
