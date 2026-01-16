<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserIntegration extends Model
{
    protected $fillable = [
        'company_id', 
        'user_id', 
        'platform', 
        'store_url', 
        'api_secret', 
        'is_active', 
        'last_synced_at'
    ];

    protected $hidden = [
        'api_secret',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'api_secret' => 'encrypted', // Laravel automatically encrypts/decrypts this
        'last_synced_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}