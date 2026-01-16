<?php

namespace App\Services\Couriers\Strategy;

use App\Services\Couriers\CourierInterface;
use App\Models\Shipment;
use App\Models\ShipmentQuote;

class MockCourierService implements CourierInterface
{
    public function getCarrierCode(): string
    {
        return 'mock_express';
    }

    public function getRates(array $data): array
    {
        // Return a dummy rate for testing/fallback
        return [
            [
                'courier_name' => 'Mock Express',
                'service_name' => 'Standard Road',
                'price_cents' => 2500, // $25.00
                'currency' => 'AUD',
                'eta' => '2-3 Days',
                'token' => 'mock_token_' . uniqid(),
            ]
        ];
    }

    public function createConsignment(Shipment $shipment, ShipmentQuote $quote): array
    {
        // 1. In a real integration, you would send JSON/XML to the courier API here.
        // 2. The courier would respond with a Label URL and Consignment ID.
        
        return [
            'consignment_number' => 'MOCK-CON-' . strtoupper(uniqid()),
            'tracking_number' => 'TRK-' . strtoupper(uniqid()),
            'label_url' => 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf', // Dummy PDF for testing
        ];
    }

    public function trackShipment(string $trackingNumber): array
    {
        return [
            'status' => 'In Transit',
            'history' => [
                ['status' => 'Picked Up', 'timestamp' => now()->subDay()],
                ['status' => 'In Transit', 'timestamp' => now()],
            ]
        ];
    }
}