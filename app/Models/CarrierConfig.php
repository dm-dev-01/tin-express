<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarrierConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'carrier_code',
        'account_code',
        'api_key',
        'api_secret',
        'environment',
        'extra_settings',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'extra_settings' => 'array',
            // Enterprise Security: Encrypt these fields in the DB automatically
            'api_key' => 'encrypted', 
            'api_secret' => 'encrypted', 
        ];
    }
}