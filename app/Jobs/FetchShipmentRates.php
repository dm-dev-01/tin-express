<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\Shipment;
use App\Models\ShipmentQuote;
use App\Services\Pricing\RateService;

class FetchShipmentRates implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $shipment;

    /**
     * Create a new job instance.
     */
    public function __construct(Shipment $shipment)
    {
        $this->shipment = $shipment;
    }

    /**
     * Execute the job.
     */
    public function handle(RateService $rateService): void
    {
        Log::info("Job: Fetching rates for Shipment #{$this->shipment->id}");

        try {
            // 1. Prepare Standard Payload
            // We map the shipment model to the array structure RateService expects.
            // This ensures logic is identical to manual frontend requests.
            $payload = [
                'sender_suburb'     => $this->shipment->sender_suburb,
                'sender_state'      => $this->shipment->sender_state,
                'sender_postcode'   => $this->shipment->sender_postcode,
                'receiver_suburb'   => $this->shipment->receiver_suburb,
                'receiver_state'    => $this->shipment->receiver_state,
                'receiver_postcode' => $this->shipment->receiver_postcode,
                'items'             => $this->shipment->items->map(function($item) {
                    return [
                        'weight'   => (float) $item->weight,
                        'length'   => (float) $item->length,
                        'width'    => (float) $item->width,
                        'height'   => (float) $item->height,
                        'quantity' => (int) $item->quantity,
                        'type'     => $item->type
                    ];
                })->toArray()
            ];

            // 2. Call the Standard Service
            $rates = $rateService->getRates($payload);

            // 3. Clear old quotes (if re-running)
            $this->shipment->quotes()->delete();

            // 4. Save New Quotes
            foreach ($rates as $rate) {
                ShipmentQuote::create([
                    'shipment_id'  => $this->shipment->id,
                    'courier_name' => $rate['courier_name'],
                    'service_name' => $rate['service_name'],
                    'service_code' => $rate['service_code'] ?? 'STD',
                    'price_cents'  => $rate['price_cents'] ?? 0,
                    'eta'          => $rate['eta'] ?? ''
                ]);
            }

            Log::info("Job: Saved " . count($rates) . " rates for Shipment #{$this->shipment->id}");

        } catch (\Exception $e) {
            Log::error("Job Failed: Could not fetch rates for Shipment #{$this->shipment->id}. Error: " . $e->getMessage());
            // Optional: $this->fail($e); to mark job as failed in DB
        }
    }
}