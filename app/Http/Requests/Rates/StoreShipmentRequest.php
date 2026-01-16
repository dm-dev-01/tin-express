<?php

namespace App\Http\Requests\Rates;

use Illuminate\Foundation\Http\FormRequest;

class StoreShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sender_name' => 'required|string',
            'sender_postcode' => 'required|string',
            'sender_suburb' => 'required|string',
            'sender_state' => 'required|string',
            
            'receiver_name' => 'required|string',
            'receiver_address' => 'required|string',
            'receiver_suburb' => 'required|string',
            'receiver_state' => 'required|string',
            'receiver_postcode' => 'required|string',
            
            'items' => 'required|array|min:1',
            // --- FIX IS HERE ---
            'items.*.type' => 'nullable|string', 
            
            'items.*.length' => 'required|numeric|min:1',
            'items.*.width' => 'required|numeric|min:1',
            'items.*.height' => 'required|numeric|min:1',
            'items.*.weight' => 'required|numeric|min:0.1',
            'items.*.quantity' => 'required|integer|min:1',
        ];
    }
}