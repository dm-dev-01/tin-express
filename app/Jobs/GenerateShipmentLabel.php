<?php

namespace App\Jobs;

use App\Models\Shipment;
use App\Models\CourierMapping;
use App\Services\Couriers\CourierStrategyFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
// FIX: Import Exception for detection
use Illuminate\Http\Client\RequestException;

class GenerateShipmentLabel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Shipment $shipment;
    
    // Increase tries to allow for multiple rate limit backoffs
    public $tries = 10;
    public $backoff = 60; // Default backoff

    public function __construct(Shipment $shipment)
    {
        $this->shipment = $shipment;
    }

    public function handle(CourierStrategyFactory $strategyFactory): void
    {
        Log::info("Job Started: Generating Label for Shipment #{$this->shipment->id} ({$this->shipment->courier_name})");

        if ($this->shipment->label_url) {
            Log::info("Job Skipped: Label already exists for Shipment #{$this->shipment->id}");
            return;
        }

        $mapping = CourierMapping::where('name_in_app', $this->shipment->courier_name)->first();
        
        if (!$mapping || !$mapping->carrierConfig) {
            Log::error("Job Failed: No configuration found for courier '{$this->shipment->courier_name}'");
            $this->fail(new \Exception("Missing Carrier Configuration")); 
            return;
        }

        try {
            $strategy = $strategyFactory->createStrategy($mapping->carrierConfig);
            
            $labelUrl = $strategy->generateLabel($this->shipment->consignment_number);

            if ($labelUrl) {
                $this->shipment->update(['label_url' => $labelUrl]);
                Log::info("Job Success: Label saved for Shipment #{$this->shipment->id}");
            } else {
                throw new \Exception("Strategy returned null for label.");
            }
        } catch (\Throwable $e) {
            
            // --- FIX: Handle 429 Rate Limiting Gracefully ---
            // If the API says "Too Many Requests", we don't fail. We wait.
            if ($e->getCode() === 429 || 
               ($e instanceof RequestException && $e->response->status() === 429)) {
                
                Log::warning("Job Rate Limited (429). Releasing back to queue for 2 minutes.");
                
                // Release job back to queue, available in 120 seconds
                $this->release(120); 
                return;
            }
            // ------------------------------------------------

            Log::error("Job Exception: " . $e->getMessage());
            throw $e; 
        }
    }
}