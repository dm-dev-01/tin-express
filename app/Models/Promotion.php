<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'description', 'type', 'value', 'max_uses', 
        'current_uses', 'min_spend_cents', 'starts_at', 'expires_at', 'is_active'
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Check if promo is valid for a given quote amount
     */
    public function isValid($cartTotalCents): bool
    {
        if (!$this->is_active) return false;
        if ($this->expires_at && now()->gt($this->expires_at)) return false;
        if ($this->starts_at && now()->lt($this->starts_at)) return false;
        if ($this->max_uses && $this->current_uses >= $this->max_uses) return false;
        if ($this->min_spend_cents && $cartTotalCents < $this->min_spend_cents) return false;

        return true;
    }

    /**
     * Calculate discount amount (cents)
     */
    public function calculateDiscount($totalCents)
    {
        if ($this->type === 'fixed') {
            // Value is in dollars, convert to cents
            return min($totalCents, $this->value * 100); 
        }

        // Percentage
        return $totalCents * ($this->value / 100);
    }
}