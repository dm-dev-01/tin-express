<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\UserIntegration;
use App\Services\Integrations\ShopifyService;

class SyncShopifyStore implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $integration;

    /**
     * Create a new job instance.
     */
    public function __construct(UserIntegration $integration)
    {
        $this->integration = $integration;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Auto-Sync: Starting for Store: {$this->integration->store_url}");

        try {
            $service = new ShopifyService($this->integration);
            
            // This reuses your existing logic (Import -> Save -> Dispatch Rate Job)
            $count = $service->importOrders();

            Log::info("Auto-Sync: Imported {$count} orders for {$this->integration->store_url}");

        } catch (\Exception $e) {
            // We log the error but do not fail the job loudly, 
            // so we don't spam your failed_jobs table if a user's token expired.
            Log::error("Auto-Sync Failed for {$this->integration->store_url}: " . $e->getMessage());
            
            // Optional: If token is invalid (401), mark integration as 'inactive' automatically?
            // For now, we just log it.
        }
    }
}