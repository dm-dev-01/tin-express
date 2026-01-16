<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class TicketMessage extends Model
{
    protected $fillable = ['support_ticket_id', 'user_id', 'message', 'attachment_path', 'attachment_name'];
    
    protected $appends = ['attachment_url'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getAttachmentUrlAttribute()
    {
        return $this->attachment_path ? Storage::url($this->attachment_path) : null;
    }
}