<?php

namespace App\Services\Couriers\Strategy;

use App\Models\Shipment;
use App\Models\ShipmentQuote;
use App\Services\Couriers\Concerns\SavesLabels;
use App\Services\Couriers\CourierInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CouriersPleaseStrategy implements CourierInterface
{
    use SavesLabels;

    protected string $baseUrlTest = 'https://api-test.couriersplease.com.au';
    protected string $baseUrlProd = 'https://api.couriersplease.com.au';
    
    protected string $accountCode;
    protected string $apiKey;
    protected bool $isTest;

    public function __construct(string $accountCode, string $apiKey, bool $isTest = true)
    {
        $this->accountCode = $accountCode;
        $this->apiKey = $apiKey;
        $this->isTest = $isTest;
    }

    public function getCarrierCode(): string
    {
        return 'couriers_please';
    }

    private function getBaseUrl(): string
    {
        return $this->isTest ? $this->baseUrlTest : $this->baseUrlProd;
    }

    public function getRates(array $data): array
    {
        $endpoint = "/v2/domestic/quote";
        
        $items = [];
        foreach ($data['items'] as $item) {
            $items[] = [
                'quantity' => (int) $item['quantity'],
                'weight' => (float) ($item['weight'] * $item['quantity']), 
                'length' => (int) $item['length'],
                'width' => (int) $item['width'],
                'height' => (int) $item['height'],
                'physicalweight' => (float) $item['weight'] 
            ];
        }

        $payload = [
            'fromSuburb' => $data['sender_suburb'],
            'fromPostcode' => $data['sender_postcode'],
            'toSuburb' => $data['receiver_suburb'],
            'toPostcode' => $data['receiver_postcode'],
            'items' => $items
        ];

        Log::info('CP Rate Request:', $payload);

        $response = $this->sendRequest('POST', $endpoint, $payload);

        if ($response->failed()) {
            Log::error('CP Rate Error:', ['body' => $response->body()]);
            return [];
        }

        $json = $response->json();
        $rates = [];

        if (isset($json['data']) && is_array($json['data'])) {
            foreach ($json['data'] as $quote) {
                // Calculate Total Price (Freight + Fuel)
                $freight = floatval($quote['CalculatedFreightCharge'] ?? 0);
                $fuel = floatval($quote['CalculatedFuelCharge'] ?? 0);
                $totalPrice = $freight + $fuel;

                $rates[] = [
                    'courier_name' => 'Couriers Please',
                    'service_name' => $quote['RateCardDescription'] ?? 'Standard',
                    'service_code' => $quote['RateCardCode'], 
                    'price_cents' => (int) round($totalPrice * 100),
                    'currency' => 'AUD',
                    'eta' => $quote['ETA'] ?? 'N/A',
                ];
            }
        }

        return $rates;
    }

    public function createConsignment(Shipment $shipment, ShipmentQuote $quote): array
    {
        $endpoint = "/v2/domestic/shipment/create";

        $items = [];
        foreach ($shipment->items as $item) {
            $items[] = [
                'quantity' => (int) $item->quantity,
                'length' => (int) $item->length,
                'width' => (int) $item->width,
                'height' => (int) $item->height,
                'physicalweight' => number_format($item->weight, 2)
            ];
        }

        $payload = [
            'pickupDeliveryChoiceID' => null,
            'pickupFirstName' => $this->splitName($shipment->sender_name)['first'],
            'pickupLastName' => $this->splitName($shipment->sender_name)['last'],
            'pickupCompanyName' => $shipment->sender_name, 
            'pickupEmail' => 'sender@example.com', 
            'pickupAddress1' => $shipment->sender_address,
            'pickupAddress2' => '',
            'pickupSuburb' => $shipment->sender_suburb,
            'pickupState' => $shipment->sender_state,
            'pickupPostcode' => $shipment->sender_postcode,
            'pickupPhone' => '0400000000', 
            'pickupIsBusiness' => 'true',

            'destinationDeliveryChoiceID' => null,
            'destinationFirstName' => $this->splitName($shipment->receiver_name)['first'],
            'destinationLastName' => $this->splitName($shipment->receiver_name)['last'],
            'destinationCompanyName' => $shipment->receiver_name,
            'destinationEmail' => 'receiver@example.com',
            'destinationAddress1' => $shipment->receiver_address,
            'destinationAddress2' => '',
            'destinationSuburb' => $shipment->receiver_suburb,
            'destinationState' => $shipment->receiver_state,
            'destinationPostcode' => $shipment->receiver_postcode,
            'destinationPhone' => '0400000000',
            'destinationIsBusiness' => 'true',

            'contactFirstName' => $this->splitName($shipment->sender_name)['first'],
            'contactLastName' => $this->splitName($shipment->sender_name)['last'],
            'contactCompanyName' => $shipment->sender_name,
            'contactEmail' => 'sender@example.com',
            'contactAddress1' => $shipment->sender_address,
            'contactAddress2' => '',
            'contactSuburb' => $shipment->sender_suburb,
            'contactState' => $shipment->sender_state,
            'contactPostcode' => $shipment->sender_postcode,
            'contactPhone' => '0400000000',
            'contactIsBusiness' => 'true',

            'referenceNumber' => (string) $shipment->id,
            'termsAccepted' => 'true',
            'dangerousGoods' => 'false',
            'rateCardId' => $quote->service_code, 
            'specialInstruction' => 'Handle with care',
            'isATL' => 'false',
            'items' => $items
        ];

        Log::info('CP Booking Request:', $payload);

        $response = $this->sendRequest('POST', $endpoint, $payload);
        
        if ($response->failed()) {
            throw new \Exception("CP Booking Failed: " . $response->body());
        }

        $json = $response->json();
        Log::info('CP Booking Response:', $json ?? []);

        $consignmentNumber = $json['data']['consignmentCode'] ?? null;

        if (!$consignmentNumber) {
            throw new \Exception("CP Booking successful but no Consignment Code returned.");
        }

        return [
            'tracking_number' => $consignmentNumber,
            'consignment_number' => $consignmentNumber,
            'label_url' => null, // Queue will handle this
        ];
    }

    /**
    /**
     * 3. GENERATE LABEL (Domestic Label API V1)
     * CLEAN VERSION: Queue handles the delay.
     */
    public function generateLabel(string $consignmentNumber): ?string
    {
        $endpoint = "/v1/domestic/shipment/label";
        
        Log::info("CP Label Request for: $consignmentNumber");

        try {
            $response = Http::withBasicAuth($this->accountCode, $this->apiKey)
                ->withHeaders(['Accept' => 'application/json'])
                // Standard Retries: 3 attempts, 1 second apart
                ->retry(3, 1000)
                ->get($this->getBaseUrl() . $endpoint, [
                    'consignmentNumber' => $consignmentNumber
                ]);

            if ($response->failed()) {
                Log::error('CP Label API Failed:', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new \Exception("Label API returned " . $response->status());
            }

            $json = $response->json();
            Log::info('CP Label Response:', $json ?? []);

            $base64Label = $json['data']['label'] ?? null;

            if ($base64Label) {
                return $this->saveLabelToDisk($base64Label, 'couriers_please', true);
            }

        } catch (\Throwable $e) {
            Log::warning("CP Label Generation Failed: " . $e->getMessage());
            
            // --- FIX: REMOVED DUMMY FALLBACK ---
            // We throw the error so the Queue Job knows it failed and will retry later.
            throw $e; 
        }

        return null;
    }

    public function trackShipment(string $trackingNumber): array
    {
        $endpoint = "/v1/domestic/locateParcel";
        $url = $this->getBaseUrl() . $endpoint . "?trackingCode=" . $trackingNumber;

        $response = Http::withBasicAuth($this->accountCode, $this->apiKey)
            ->withHeaders(['Accept' => 'application/json'])
            ->get($url);

        if ($response->failed()) {
            return ['status' => 'Unknown', 'history' => []];
        }

        $json = $response->json();
        $consignmentInfo = $json['data']['consignmentInfo'][0] ?? [];

        $status = $consignmentInfo['status'] ?? 'Unknown';
        
        $history = [];
        if (isset($consignmentInfo['itemsCoupons'][0]['trackingInfo'])) {
            foreach ($consignmentInfo['itemsCoupons'][0]['trackingInfo'] as $event) {
                $history[] = [
                    'status' => $event['action'] ?? '',
                    'timestamp' => ($event['date'] ?? '') . ' ' . ($event['time'] ?? ''),
                    'location' => $event['contractor'] ?? ''
                ];
            }
        }

        return [
            'status' => $status,
            'consignment_number' => $trackingNumber,
            'history' => $history
        ];
    }

    private function sendRequest(string $method, string $endpoint, array $data = [])
    {
        return Http::withBasicAuth($this->accountCode, $this->apiKey)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])
            ->$method($this->getBaseUrl() . $endpoint, $data);
    }

    private function splitName($fullName)
    {
        $parts = explode(' ', $fullName, 2);
        return [
            'first' => $parts[0] ?? 'Valued',
            'last' => $parts[1] ?? 'Customer'
        ];
    }
}