<?php

namespace App\Services\Couriers\Strategy;

use App\Services\Couriers\CourierInterface;
use Illuminate\Support\Facades\Http;
use Exception;

class TNTStrategy implements CourierInterface
{
    // Generic Base URL for ExpressConnect
    protected string $baseUrl = 'https://express.tnt.com/expressconnect';
    protected string $username;
    protected string $password;
    protected string $accountNumber;

    public function __construct(string $username, string $password, string $accountNumber)
    {
        $this->username = $username;
        $this->password = $password;
        $this->accountNumber = $accountNumber;
    }

    public function getCarrierCode(): string
    {
        return 'tnt';
    }

    public function getRates(array $data): array
    {
        $xml = $this->buildXmlPayload($data);

        // Append specific endpoint for pricing
        $response = Http::asForm()->post("{$this->baseUrl}/pricing/getprice", [
            'xml_in' => $xml
        ]);

        if ($response->failed() || str_contains($response->body(), 'error_description')) {
             throw new \Exception("TNT API Error: " . $response->body());
        }

        return $this->parseResponse($response->body());
    }

    private function buildXmlPayload(array $data): string
    {
        $itemsXml = '';
        $totalWeight = 0;
        $totalVolume = 0;

        foreach ($data['items'] as $item) {
            $totalWeight += $item['weight'] * $item['quantity'];
            $volume = ($item['length'] * $item['width'] * $item['height']) / 1000000;
            $totalVolume += $volume * $item['quantity'];

            $itemsXml .= "<pieceLine>
                <numberOfPieces>{$item['quantity']}</numberOfPieces>
                <weight>{$item['weight']}</weight>
                <length>{$item['length']}</length>
                <width>{$item['width']}</width>
                <height>{$item['height']}</height>
            </pieceLine>";
        }

        $date = date('Y-m-d\TH:i:s', strtotime('+1 weekday'));

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<priceRequest>
    <appId>PC</appId>
    <appVersion>3.0</appVersion>
    <priceCheck>
        <rateId>rate1</rateId>
        <sender>
            <country>AU</country>
            <town>{$data['sender_suburb']}</town>
            <postcode>{$data['sender_postcode']}</postcode>
        </sender>
        <delivery>
            <country>AU</country>
            <town>{$data['receiver_suburb']}</town>
            <postcode>{$data['receiver_postcode']}</postcode>
        </delivery>
        <collectionDateTime>{$date}</collectionDateTime>
        <product><type>N</type></product>
        <account>
            <accountNumber>{$this->accountNumber}</accountNumber>
            <accountCountry>AU</accountCountry>
        </account>
        <currency>AUD</currency>
        <priceBreakDown>true</priceBreakDown>
        <consignmentDetails>
            <totalWeight>{$totalWeight}</totalWeight>
            <totalVolume>{$totalVolume}</totalVolume>
            <totalNumberOfPieces>{$data['items'][0]['quantity']}</totalNumberOfPieces>
        </consignmentDetails>
        <pieceLineList>{$itemsXml}</pieceLineList>
    </priceCheck>
    <login>
        <userId>{$this->username}</userId>
        <password>{$this->password}</password>
    </login>
</priceRequest>
XML;
    }

    private function parseResponse(string $xmlString): array
    {
        $rates = [];
        try {
            $xml = simplexml_load_string($xmlString);
            if (isset($xml->priceResponse->ratedService)) {
                foreach ($xml->priceResponse->ratedService as $service) {
                    $rates[] = [
                        'courier_name' => 'TNT / FedEx',
                        'service_name' => (string)$service->product->description,
                        'price_cents' => (int)((float)$service->totalPriceExclVat * 100),
                        'currency' => (string)$service->currency,
                        'eta' => 'N/A',
                        'token' => uniqid('tnt_'),
                    ];
                }
            }
        } catch (Exception $e) {
            // Quietly handle parse errors
        }
        return $rates;
    }
    public function createConsignment(Shipment $shipment, ShipmentQuote $quote): array
    {
        // 1. Prepare Data
        // TNT requires a future date for collection (e.g., tomorrow 9am-5pm)
        $date = date('Y-m-d', strtotime('+1 weekday')); 
        
        // 2. Build XML Payload for Booking (Consignment)
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<consignmentBatch>
    <sender>
        <account>{$this->accountNumber}</account>
        <address>
            <name>{$shipment->sender_name}</name>
            <line1>{$shipment->sender_address}</line1>
            <town>{$shipment->sender_suburb}</town>
            <province>{$shipment->sender_state}</province>
            <postcode>{$shipment->sender_postcode}</postcode>
            <country>AU</country>
        </address>
    </sender>
    <consignment>
        <conref>{$shipment->id}</conref>
        <details>
            <receiver>
                <name>{$shipment->receiver_name}</name>
                <line1>{$shipment->receiver_address}</line1>
                <town>{$shipment->receiver_suburb}</town>
                <province>{$shipment->receiver_state}</province>
                <postcode>{$shipment->receiver_postcode}</postcode>
                <country>AU</country>
            </receiver>
            <deliveryInstructions>Please leave at front door if unattended</deliveryInstructions>
            <package>
                <itemReference>Box 1</itemReference>
                <description>General Freight</description>
            </package>
        </details>
    </consignment>
</consignmentBatch>
XML;

        // 3. Send to TNT API
        $response = Http::asForm()->post("{$this->baseUrl}/shipping/ship", [
            'xml_in' => "GET_RESULT:{$xml}", // TNT Syntax
            'access_code' => 'ACCESS_CODE_HERE' // You usually need a separate access code for shipping vs pricing
        ]);

        if ($response->failed()) {
            throw new \Exception("TNT Booking Failed: " . $response->body());
        }

        // 4. Parse Response (Mocked parser here as response depends on exact account setup)
        // In reality, you parse the XML to find <consignmentNumber> and label URL.
        
        return [
            'tracking_number' => 'TNT-' . strtoupper(uniqid()), // Replace with parsed value
            'consignment_number' => 'CON-' . strtoupper(uniqid()),
            'label_url' => null, // TNT usually returns raw PDF bytes, needs file storage logic
        ];
    }
    // ... existing code ...
    // ... existing code ...
    public function trackShipment(string $trackingNumber): array
    {
        return ['status' => 'Not Implemented'];
    }
}
}
}