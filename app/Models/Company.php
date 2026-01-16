<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    // These are the fields we allow to be mass-assigned
    protected $fillable = [
        'abn',
        'abn_status',
        'entity_name',
        'trading_name',
        'address_line_1',
        'suburb',
        'state',
        'postcode',
        'country_code',
        'currency',
        'timezone',
        'wallet_balance',
        'billing_email',
    ];

    protected $casts = [
        'wallet_balance' => 'decimal:2',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function shipments()
    {
        return $this->hasMany(Shipment::class);
    }
}