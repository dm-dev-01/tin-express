<?php

namespace App\Services\Integrations;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\UserIntegration;
use App\Models\Shipment;
use App\Models\Company;
use App\Models\User;
use App\Jobs\FetchShipmentRates; // Job for rates
use App\Services\Pricing\RateService;

class ShopifyService
{
    protected $integration;
    protected $graphqlUrl;

    public function __construct(UserIntegration $integration)
    {
        $this->integration = $integration;
        
        // Clean and format the store URL
        $domain = preg_replace('#^https?://#', '', $integration->store_url);
        $domain = rtrim($domain, '/');
        if (!str_contains($domain, '.')) {
            $domain .= '.myshopify.com';
        }
        
        $this->graphqlUrl = "https://{$domain}/admin/api/2024-01/graphql.json";
    }

    /**
     * Validate credentials by fetching the Shop Name.
     */
    public function validateConnection(): bool
    {
        $query = '{ shop { name } }';
        $response = $this->query($query);
        return !isset($response['errors']);
    }

    /**
     * IMPORT FLOW: Fetch unfulfilled orders and save them locally.
     */
    public function importOrders()
    {
        $query = <<<'GQL'
        {
          shop {
            name
            billingAddress {
              address1
              city
              provinceCode
              zip
              phone
            }
          }
          orders(first: 20, query: "status:open fulfillment_status:unfulfilled financial_status:paid") {
            edges {
              node {
                id
                legacyResourceId
                name
                email
                totalWeight
                shippingAddress {
                  address1
                  address2
                  city
                  provinceCode
                  zip
                  firstName
                  lastName
                  phone
                }
              }
            }
          }
        }
        GQL;

        $data = $this->query($query);

        if (isset($data['errors'])) {
            Log::error("Shopify GraphQL Error: " . json_encode($data['errors']));
            throw new \Exception("Sync Failed: " . ($data['errors'][0]['message'] ?? 'Unknown Error'));
        }

        $shop = $data['data']['shop'];
        
        $storeSender = [
            'name'     => $shop['name'],
            'address'  => $shop['billingAddress']['address1'] ?? '',
            'suburb'   => $shop['billingAddress']['city'] ?? '',
            'state'    => $shop['billingAddress']['provinceCode'] ?? '',
            'postcode' => $shop['billingAddress']['zip'] ?? '',
            'contact'  => $shop['billingAddress']['phone'] ?? '',
        ];

        $orders = $data['data']['orders']['edges'];
        $count = 0;

        foreach ($orders as $edge) {
            $node = $edge['node'];

            // Skip if already imported
            if (Shipment::where('external_order_id', (string)$node['legacyResourceId'])->exists()) {
                continue;
            }

            try {
                $this->createShipment($node, $storeSender);
                $count++;
            } catch (\Exception $e) {
                Log::error("Failed to import Shopify Order {$node['name']}: " . $e->getMessage());
            }
        }

        $this->integration->update(['last_synced_at' => now()]);
        return $count;
    }

    /**
     * FULFILLMENT FLOW: Update Shopify when we book a shipment.
     */
    /**
     * FULFILLMENT FLOW: Update Shopify when we book a shipment.
     */
    public function markOrderFulfilled(Shipment $shipment)
    {
        $shopifyOrderId = $shipment->external_order_id;
        
        Log::info("Shopify Sync: Starting Fulfillment for Order #{$shipment->external_order_number}");

        // 1. Get FulfillmentOrder ID
        $queryIds = <<<'GQL'
        query($id: ID!) {
          order(id: $id) {
            fulfillmentOrders(first: 5) {
              edges {
                node {
                  id
                  status
                }
              }
            }
          }
        }
        GQL;

        $gqlOrderId = str_starts_with($shopifyOrderId, 'gid://') 
            ? $shopifyOrderId 
            : "gid://shopify/Order/{$shopifyOrderId}";

        $response = $this->query($queryIds, ['id' => $gqlOrderId]);
        
        $fulfillmentOrders = $response['data']['order']['fulfillmentOrders']['edges'] ?? [];
        
        // Find the first OPEN fulfillment order
        $fulfillmentOrderId = null;
        foreach($fulfillmentOrders as $edge) {
            if ($edge['node']['status'] === 'OPEN') {
                $fulfillmentOrderId = $edge['node']['id'];
                break;
            }
        }

        if (!$fulfillmentOrderId) {
            Log::info("Shopify Sync: Order {$shipment->external_order_number} has no OPEN fulfillment orders. It might already be fulfilled.");
            return;
        }

        // 2. Resolve the correct Tracking URL
        $trackingUrl = $this->getTrackingUrl($shipment);

        // 3. Create Fulfillment
        $mutation = <<<'GQL'
        mutation fulfillmentCreateV2($fulfillment: FulfillmentV2Input!) {
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
        }
        GQL;

        $variables = [
            'fulfillment' => [
                'lineItemsByFulfillmentOrder' => [
                    [
                        'fulfillmentOrderId' => $fulfillmentOrderId
                    ]
                ],
                'notifyCustomer' => true,
                'trackingInfo' => [
                    // We map the correct courier name and dynamic URL here
                    'company' => $shipment->courier_name ?? 'Other', 
                    'number' => $shipment->tracking_number,
                    'url' => $trackingUrl
                ]
            ]
        ];

        $result = $this->query($mutation, $variables);

        if (!empty($result['data']['fulfillmentCreateV2']['userErrors'])) {
            $errors = json_encode($result['data']['fulfillmentCreateV2']['userErrors']);
            
            // Retry logic: If "Courier not valid", retry as "Other"
            if (str_contains($errors, "tracking company is invalid")) {
                Log::warning("Shopify rejected courier '{$shipment->courier_name}'. Retrying as 'Other'.");
                $variables['fulfillment']['trackingInfo']['company'] = 'Other';
                $this->query($mutation, $variables); // Retry once
            } else {
                throw new \Exception("Shopify Fulfillment Failed: " . $errors);
            }
        }

        Log::info("✅ Shopify Sync: Successfully marked {$shipment->external_order_number} as Fulfilled with URL: {$trackingUrl}");
    }

    /**
     * Helper: Generate specific tracking URLs for Australian Couriers
     */
    private function getTrackingUrl(Shipment $shipment): string
    {
        $number = $shipment->tracking_number;
        $courier = strtolower($shipment->courier_name ?? '');

        return match (true) {
            str_contains($courier, 'hunter') => "https://www.hunterexpress.com.au/home/tracking?connote={$number}",
            str_contains($courier, 'couriers please') => "https://www.couriersplease.com.au/tools-track/tracking-result?no={$number}",
            str_contains($courier, 'tnt') => "https://www.tnt.com/express/en_au/site/shipping-tools/tracking.html?searchType=con&cons={$number}",
            str_contains($courier, 'aramex') || str_contains($courier, 'fastway') => "https://www.aramex.com.au/tools/track?id={$number}",
            str_contains($courier, 'startrack') => "https://startrack.com.au/track/search?q={$number}",
            str_contains($courier, 'auspost') || str_contains($courier, 'australia post') => "https://auspost.com.au/mypost/track/#/details/{$number}",
            str_contains($courier, 'allied') => "http://www.alliedexpress.com.au/cgi-bin/search.cgi?{$number}",
            default => "https://www.google.com/search?q={$number}" // Fallback
        };
    }

    /**
     * Internal Helper to create the shipment locally
     */
    private function createShipment($order, $storeSender)
    {
        $addressNode = $order['shippingAddress'] ?? null;
        if (!$addressNode) return; 

        $userId = $this->integration->user_id ?? Auth::id();
        if (!$userId) {
            $userId = User::where('company_id', $this->integration->company_id)->value('id') ?? 1;
        }

        if (empty($storeSender['address'])) {
             $dbCompany = Company::find($this->integration->company_id);
             $sender = [
                'name' => $dbCompany->entity_name,
                'address' => $dbCompany->address,
                'suburb' => $dbCompany->suburb,
                'state' => $dbCompany->state,
                'postcode' => $dbCompany->postcode,
                'contact' => $dbCompany->phone,
             ];
        } else {
             $sender = $storeSender;
        }

        DB::beginTransaction();
        try {
            $shipment = Shipment::create([
                'company_id' => $this->integration->company_id,
                'user_id'    => $userId,
                'source'     => 'shopify',
                'external_order_id'     => (string)$order['legacyResourceId'],
                'external_order_number' => (string)$order['name'],
                'status'     => 'draft',
                'sender_name'    => $sender['name'],
                'sender_address' => $sender['address'],
                'sender_suburb'  => $sender['suburb'],
                'sender_state'   => $sender['state'],
                'sender_postcode'=> $sender['postcode'],
                'sender_contact' => $sender['contact'],
                'receiver_name'    => trim(($addressNode['firstName'] ?? '') . ' ' . ($addressNode['lastName'] ?? '')),
                'receiver_address' => trim(($addressNode['address1'] ?? '') . ' ' . ($addressNode['address2'] ?? '')),
                'receiver_suburb'  => $addressNode['city'] ?? '',
                'receiver_state'   => $addressNode['provinceCode'] ?? '',
                'receiver_postcode'=> $addressNode['zip'] ?? '',
                'receiver_contact' => $addressNode['phone'] ?? ($order['email'] ?? ''),
            ]);

            $weightKg = max(0.5, ($order['totalWeight'] ?? 1000) / 1000);

            $shipment->items()->create([
                'description' => 'Order ' . $order['name'],
                'type'        => 'carton',
                'length'      => 20, 'width' => 20, 'height' => 20,
                'weight'      => $weightKg,
                'quantity'    => 1
            ]);

            DB::commit();
            Log::info("✅ Imported {$order['name']} - Shipment ID: {$shipment->id}");

            // Background Job for Rates
            FetchShipmentRates::dispatch($shipment);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function query($query, $variables = [])
    {
        $payload = ['query' => $query];
        if (!empty($variables)) $payload['variables'] = $variables;

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $this->integration->api_secret,
            'Content-Type' => 'application/json'
        ])->post($this->graphqlUrl, $payload);

        return $response->json();
    }
}