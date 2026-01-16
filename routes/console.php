<?php

use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use App\Models\Shipment;
use App\Models\UserIntegration;

// Test command
Schedule::command('inspire')->hourly();

// --- ENTERPRISE SCHEDULER ---
// Run the sync manager every 15 minutes.
// withoutOverlapping() ensures we don't spawn 10 processes if one is stuck.
Schedule::command('integrations:sync-shopify')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->onOneServer(); // Important if you ever scale to multiple servers (AWS/DigitalOcean)

    Artisan::command('debug:shopify {shipment_id}', function ($shipment_id) {
    $this->info("--- STARTING SHOPIFY DIAGNOSTICS FOR SHIPMENT #{$shipment_id} ---");

    // 1. Load Data
    $shipment = Shipment::find($shipment_id);
    if (!$shipment) {
        $this->error("Shipment not found.");
        return;
    }

    $integration = UserIntegration::where('company_id', $shipment->company_id)
        ->where('platform', 'shopify')
        ->first();

    if (!$integration) {
        $this->error("No Shopify integration found.");
        return;
    }

    $headers = [
        'X-Shopify-Access-Token' => $integration->api_secret,
        'Content-Type' => 'application/json'
    ];
    $url = "https://{$integration->store_url}/admin/api/2024-01/graphql.json";

    $this->info("Target Store: {$integration->store_url}");
    $this->info("External Order ID: {$shipment->external_order_id}");

    // 2. Fetch Fulfillment Order ID
    $this->comment("Fetching Fulfillment Order Data...");
    
    $queryId = '{ 
        order(id: "gid://shopify/Order/' . $shipment->external_order_id . '") { 
            name
            displayFulfillmentStatus
            fulfillmentOrders(first: 5) { 
                edges { 
                    node { 
                        id 
                        status 
                    } 
                } 
            } 
        } 
    }';

    $response = Http::withHeaders($headers)->post($url, ['query' => $queryId])->json();
    
    // Dump Raw Response for analysis
    $this->line("--- RAW SHOPIFY RESPONSE ---");
    dump($response); 

    // Extract Data
    $fulfillmentOrderId = $response['data']['order']['fulfillmentOrders']['edges'][0]['node']['id'] ?? null;
    $status = $response['data']['order']['fulfillmentOrders']['edges'][0]['node']['status'] ?? 'UNKNOWN';
    $displayStatus = $response['data']['order']['displayFulfillmentStatus'] ?? 'UNKNOWN';

    $this->info("Fulfillment Order ID: " . ($fulfillmentOrderId ?? "NULL"));
    $this->info("Internal Status: {$status}");
    $this->info("Display Status: {$displayStatus}");

    // 3. Attempt Fulfillment if Valid
    if ($fulfillmentOrderId && $status === 'OPEN') {
        $this->comment("Attempting to Push Fulfillment...");
        
        $mutation = 'mutation fulfillmentCreateV2($fulfillment: FulfillmentV2Input!) { 
            fulfillmentCreateV2(fulfillment: $fulfillment) { 
                fulfillment { 
                    status 
                    displayStatus 
                } 
                userErrors { 
                    field 
                    message 
                } 
            } 
        }';
        
        $variables = [
            'fulfillment' => [
                'lineItemsByFulfillmentOrder' => [['fulfillmentOrderId' => $fulfillmentOrderId]],
                'notifyCustomer' => true,
                'trackingInfo' => [
                    'company' => 'Other', // Hardcoded 'Other' for safety
                    'number' => $shipment->tracking_number, 
                    'url' => 'https://google.com'
                ]
            ]
        ];

        $res2 = Http::withHeaders($headers)->post($url, ['query' => $mutation, 'variables' => $variables])->json();
        
        $this->line("--- FULFILLMENT RESPONSE ---");
        dump($res2);
    } else {
        $this->warn("Skipping fulfillment push. Status must be 'OPEN', but got '{$status}'.");
    }

    $this->info("--- DIAGNOSTICS COMPLETE ---");
});