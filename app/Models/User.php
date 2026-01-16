<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // <--- CRITICAL FIX

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens; // <--- CRITICAL FIX

    // --- DEFINE ROLES ---
    const ROLE_SUPER_ADMIN = 'super_admin';
    const ROLE_COMPANY_ADMIN = 'company_admin';
    const ROLE_COMPANY_USER = 'company_user';

    protected $fillable = [
        'company_id',
        'first_name',
        'last_name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            // 'password' => 'hashed', // <--- DISABLED TO PREVENT DOUBLE HASHING
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isCompanyAdmin(): bool
    {
        return $this->role === self::ROLE_COMPANY_ADMIN;
    }
}