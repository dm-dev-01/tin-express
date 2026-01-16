<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentQuote extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'courier_name',
        'service_name',
        'service_code',
        'price_cents',
        'eta'
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }
}