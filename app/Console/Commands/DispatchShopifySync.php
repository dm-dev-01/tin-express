<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UserIntegration;
use App\Jobs\SyncShopifyStore;
use Illuminate\Support\Facades\Log;

class DispatchShopifySync extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'integrations:sync-shopify';

    /**
     * The console command description.
     */
    protected $description = 'Dispatch sync jobs for all active Shopify integrations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info("Scheduler: Checking for Shopify stores to sync...");

        // 1. Find all Active Shopify Integrations
        // We can optimize by only picking ones synced > 10 mins ago if needed.
        $integrations = UserIntegration::where('platform', 'shopify')
            ->where('is_active', true)
            ->get();

        if ($integrations->isEmpty()) {
            $this->info('No active integrations found.');
            return;
        }

        $this->info("Found {$integrations->count()} active stores. Dispatching jobs...");

        // 2. Dispatch a Job for each one
        foreach ($integrations as $integration) {
            SyncShopifyStore::dispatch($integration);
        }

        $this->info("Dispatched {$integrations->count()} jobs to the queue.");
        Log::info("Scheduler: Dispatched {$integrations->count()} sync jobs.");
    }
}