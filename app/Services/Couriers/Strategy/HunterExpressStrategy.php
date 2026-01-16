<?php

namespace App\Services\Couriers\Strategy;

use App\Models\Shipment;
use App\Models\ShipmentQuote;
use App\Services\Couriers\Concerns\SavesLabels;
use App\Services\Couriers\CourierInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HunterExpressStrategy implements CourierInterface
{
    use SavesLabels;

    protected string $baseUrl = 'https://api.transvirtual.com.au/Api';
    protected string $tvNumber;     
    protected string $authToken;    
    protected string $customerCode;

    public function __construct(string $authToken, string $tvNumber, string $customerCode)
    {
        $this->authToken = $authToken;
        $this->tvNumber = $tvNumber;
        $this->customerCode = $customerCode; 
    }

    public function getCarrierCode(): string
    {
        return 'hunters';
    }

    public function getRates(array $data): array
    {
        $payload = [
            'CustomerCode' => $this->customerCode,
            'SenderSuburb' => $data['sender_suburb'],
            'SenderState' => $data['sender_state'],
            'SenderPostcode' => $data['sender_postcode'],
            'ReceiverSuburb' => $data['receiver_suburb'],
            'ReceiverState' => $data['receiver_state'],
            'ReceiverPostcode' => $data['receiver_postcode'],
            'Rows' => []
        ];

        foreach ($data['items'] as $item) {
            $payload['Rows'][] = [
                'QtyDecimal' => (float) $item['quantity'],
                'Description' => 'General Freight',
                'Weight' => (float) ($item['weight'] * $item['quantity']),
                'Length' => (float) $item['length'],
                'Width' => (float) $item['width'],
                'Height' => (float) $item['height']
            ];
        }

        Log::info('Hunter Express Rate Request:', $payload);

        $response = $this->sendRequest('POST', '/PriceEstimate', $payload);

        if ($response->failed()) {
            Log::error('Hunter Express HTTP Error:', ['body' => $response->body()]);
            return [];
        }

        $result = $response->json();
        Log::info('Hunter Express Rate Response:', $result ?? []);

        $rates = [];
        $rows = $result['Data']['Rows'] ?? [];

        foreach ($rows as $row) {
            if (isset($row['GrandPrice']) && $row['GrandPrice'] > 0) {
                $rates[] = [
                    'courier_name' => 'Hunter Express',
                    'service_name' => $row['Title'] ?? $row['ServiceType'] ?? 'Standard',
                    'service_code' => $row['ServiceType'] ?? 'EXP',
                    'price_cents' => (int) ($row['GrandPrice'] * 100),
                    'currency' => 'AUD',
                    'eta' => $row['Transit'] ?? '3-5 Days'
                ];
            }
        }

        return $rates;
    }

    public function createConsignment(Shipment $shipment, ShipmentQuote $quote): array
    {
        $payload = [
            'CustomerCode' => $this->customerCode,
            'SenderName' => $shipment->sender_name,
            'SenderAddress' => $shipment->sender_address,
            'SenderSuburb' => $shipment->sender_suburb,
            'SenderState' => $shipment->sender_state,
            'SenderPostcode' => $shipment->sender_postcode,
            'ReceiverName' => $shipment->receiver_name,
            'ReceiverAddress' => $shipment->receiver_address,
            'ReceiverSuburb' => $shipment->receiver_suburb,
            'ReceiverState' => $shipment->receiver_state,
            'ReceiverPostcode' => $shipment->receiver_postcode,
            'ReturnPdfLabels' => 'y', 
            'Rows' => []
        ];

        foreach ($shipment->items as $item) {
             $payload['Rows'][] = [
                'Qty' => (int) $item->quantity,
                'Description' => 'Carton',
                'Weight' => (int) ceil($item->weight * $item->quantity),
                'Length' => (float) $item->length,
                'Width' => (float) $item->width, 
                'Height' => (float) $item->height,
            ];
        }

        Log::info('Hunter Booking Request:', $payload);

        $response = $this->sendRequest('POST', '/Consignment', $payload);
        
        if ($response->failed()) {
            throw new \Exception("Hunter Booking Failed: " . $response->body());
        }

        $json = $response->json();
        Log::info('Hunter Booking Response:', $json ?? []);

        // 1. Access Data
        $data = $json['Data'] ?? []; 

        $consignmentNumber = $data['ConsignmentNumber'] 
                          ?? $data['consignmentNumber'] 
                          ?? $data['consignment_number'] 
                          ?? null;

        $pdfLabelsBase64 = $data['PdfLabels'] 
                        ?? $data['pdfLabels'] 
                        ?? $data['pdf_labels'] 
                        ?? null;

        // 2. Fallback for Test Mode
        if (!$consignmentNumber && $this->customerCode === 'APITEST') {
            Log::warning('Hunter Express (Test) returned no ConsignmentNumber. Generating fake one.');
            $consignmentNumber = 'TEST-HUNTER-' . mt_rand(1000, 9999);
        }

        // 3. Save Label Immediately (Optimization for Hunter)
        $labelUrl = null;
        if ($pdfLabelsBase64) {
            $labelUrl = $this->saveLabelToDisk($pdfLabelsBase64, 'hunters', true);
        } elseif ($this->customerCode === 'APITEST') {
            $labelUrl = 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf';
        }

        // Return label_url so Controller saves it immediately.
        // This prevents the Queue Job from running unnecessarily.
        return [
            'tracking_number' => $consignmentNumber,
            'consignment_number' => $consignmentNumber,
            'label_url' => $labelUrl,
        ];
    }

    /**
     * Interface Requirement.
     * Used by the Queue Job only if the initial booking failed to save the label.
     */
    public function generateLabel(string $consignmentNumber): ?string
    {
        $payload = ['ConsignmentNumber' => $consignmentNumber];
        $response = $this->sendRequest('POST', '/ConsignmentQuery', $payload);

        if ($response->successful()) {
            $data = $response->json();
            // Hunter often returns the label in the query response too
            $base64 = $data['Data']['PdfLabels'] ?? null;
            if ($base64) {
                return $this->saveLabelToDisk($base64, 'hunters', true);
            }
        }

        // Fallback for visual testing
        if ($this->customerCode === 'APITEST') {
             return 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf';
        }

        return null;
    }

    public function trackShipment(string $trackingNumber): array
    {
        $payload = ['ConsignmentNumber' => $trackingNumber];
        $response = $this->sendRequest('POST', '/ConsignmentQuery', $payload);

        if ($response->failed()) {
            return ['status' => 'Unknown', 'history' => []];
        }

        $data = $response->json()['Data'] ?? [];
        return [
            'status' => 'Booked',
            'consignment_number' => $data['Number'] ?? $trackingNumber,
            'sender' => $data['SenderName'] ?? '',
            'receiver' => $data['ReceiverName'] ?? '',
            'history' => []
        ];
    }

    private function sendRequest(string $method, string $endpoint, array $data)
    {
        $authHeader = trim($this->tvNumber) . '|' . trim($this->authToken);

        return Http::withHeaders([
            'Authorization' => $authHeader,
            'Content-Type' => 'application/json',
            'Accept' => '*/*'
        ])->$method($this->baseUrl . $endpoint, $data);
    }
}