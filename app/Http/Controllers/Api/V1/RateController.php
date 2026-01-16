<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\ShipmentQuote; // <--- Added Import
use App\Services\Pricing\RateService; // <--- Ensure this Service exists from previous steps
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RateController extends Controller
{
    protected $rateService;

    public function __construct(RateService $rateService)
    {
        $this->rateService = $rateService;
    }

    public function store(Request $request)
    {
        // 1. Validation
        $validated = $request->validate([
            'sender_name' => 'required|string',
            'sender_address' => 'nullable|string',
            'sender_suburb' => 'required|string',
            'sender_postcode' => 'required|string',
            'sender_state' => 'required|string',
            'receiver_name' => 'required|string',
            'receiver_address' => 'required|string',
            'receiver_suburb' => 'required|string',
            'receiver_postcode' => 'required|string',
            'receiver_state' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.weight' => 'required|numeric',
            'items.*.length' => 'required|numeric',
            'items.*.width' => 'required|numeric',
            'items.*.height' => 'required|numeric',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.type' => 'required|string',
        ]);

        // 2. Create Shipment & Save Quotes in a Transaction
        return DB::transaction(function () use ($validated, $request) {
            $user = $request->user();

            // A. Create the Shipment (Draft)
            $shipment = Shipment::create([
                'company_id' => $user ? $user->company_id : null, // Handle guest quotes if needed
                'user_id' => $user ? $user->id : null,
                'sender_name' => $validated['sender_name'],
                'sender_address' => $validated['sender_address'] ?? 'N/A',
                'sender_suburb' => $validated['sender_suburb'],
                'sender_state' => $validated['sender_state'],
                'sender_postcode' => $validated['sender_postcode'],
                'receiver_name' => $validated['receiver_name'],
                'receiver_address' => $validated['receiver_address'],
                'receiver_suburb' => $validated['receiver_suburb'],
                'receiver_state' => $validated['receiver_state'],
                'receiver_postcode' => $validated['receiver_postcode'],
                'status' => 'draft'
            ]);

            // B. Create Items
            foreach ($validated['items'] as $item) {
                ShipmentItem::create([
                    'shipment_id' => $shipment->id,
                    'type' => $item['type'],
                    'length' => $item['length'],
                    'width' => $item['width'],
                    'height' => $item['height'],
                    'weight' => $item['weight'],
                    'quantity' => $item['quantity'],
                ]);
            }

            // C. Get Rates from Service (Logic from previous steps)
            // Note: We are passing the array of items to the service
            $rates = $this->rateService->getRates($validated);

            // D. SAVE THE RATES TO DATABASE
            $savedQuotes = [];
            foreach ($rates as $rate) {
                $savedQuotes[] = ShipmentQuote::create([
                    'shipment_id' => $shipment->id,
                    'courier_name' => $rate['courier_name'],
                    'service_name' => $rate['service_name'],
                    'service_code' => $rate['service_code'] ?? 'STD', 
                    'price_cents' => $rate['price_cents'] ?? ($rate['price'] ?? 0),
                    'eta' => $rate['eta'] ?? 'TBC'
                ]);
            }

            return response()->json([
                'message' => 'Rates calculated successfully',
                'shipment_id' => $shipment->id,
                'rates' => $savedQuotes // Return the DB models
            ]);
        });
    }
}