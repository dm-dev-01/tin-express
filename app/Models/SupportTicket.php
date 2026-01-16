<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    protected $fillable = ['user_id', 'company_id', 'shipment_id', 'subject', 'status', 'priority'];

    public function messages()
    {
        return $this->hasMany(TicketMessage::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }
    
    // Helper to see last update time
    public function lastMessage()
    {
        return $this->hasOne(TicketMessage::class)->latestOfMany();
    }
}