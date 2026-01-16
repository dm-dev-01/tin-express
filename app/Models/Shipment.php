<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'sender_name',
        'sender_address',
        'sender_suburb',
        'sender_state',
        'sender_postcode',
        'receiver_name',
        'receiver_address',
        'receiver_suburb',
        'receiver_state',
        'receiver_postcode',
        'courier_name',
        'service_name',
        'status',
        'total_price_cents',
        'tracking_number',
        'consignment_number',
        'label_url',
        'source',                // 'manual' or 'shopify'
        'external_order_id',     // Shopify Order ID
        'external_order_number',
    ];

    

    // --- RELATIONSHIPS (These were likely missing) ---

    // A shipment belongs to a Company
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // A shipment belongs to the User who created it
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // A shipment has many Items (boxes, pallets, etc.)
    public function items()
    {
        return $this->hasMany(ShipmentItem::class);
    }
    public function quotes()
    {
        return $this->hasMany(ShipmentQuote::class);
    }
    public function getLabelUrlAttribute($value)
    {
        // 1. If null, return null
        if (!$value) return null;

        // 2. Legacy Support: If it's already a full URL (e.g. from a dummy fallback), return it.
        if (str_starts_with($value, 'http')) {
            return $value;
        }

        // 3. ENTERPRISE STANDARD: Generate URL dynamically using Named Routes.
        // This is robust. If you change your API to /v2/, this line effectively updates itself.
        return route('api.v1.shipments.label', ['shipment' => $this->id]);
    }
}