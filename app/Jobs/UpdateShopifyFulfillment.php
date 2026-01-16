<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\Shipment;
use App\Models\UserIntegration;
use App\Services\Integrations\ShopifyService;

class UpdateShopifyFulfillment implements ShouldQueue
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
    public function handle(): void
    {
        // 1. Check if this is a Shopify Shipment
        if ($this->shipment->source !== 'shopify' || !$this->shipment->external_order_id) {
            return; // Not a Shopify order, skip.
        }

        Log::info("Job: Updating Shopify Fulfillment for Shipment #{$this->shipment->id}");

        // 2. Find the Integration Config
        // We need the API Key to talk to Shopify
        $integration = UserIntegration::where('company_id', $this->shipment->company_id)
            ->where('platform', 'shopify')
            ->where('is_active', true)
            ->first();

        if (!$integration) {
            Log::error("Job Failed: No active Shopify connection found for Company {$this->shipment->company_id}");
            return;
        }

        try {
            // 3. Call the Service to Push Update
            $service = new ShopifyService($integration);
            $service->markOrderFulfilled($this->shipment);
            
            Log::info("Job: Successfully fulfilled Shopify Order #{$this->shipment->external_order_number}");

        } catch (\Exception $e) {
            Log::error("Job Failed: Shopify Fulfillment Error: " . $e->getMessage());
            // We do not fail() the job because the shipment is already booked. 
            // We don't want to retry infinitely if Shopify is down or the order is deleted.
        }
    }
}