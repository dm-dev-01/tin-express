<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'type', // box, satchel, pallet
        'length',
        'width',
        'height',
        'weight',
        'quantity'
    ];

    // Inverse relationship: An item belongs to a shipment
    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }
}