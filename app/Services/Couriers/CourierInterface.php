<?php

namespace App\Services\Couriers;

// --- ADD THESE TWO LINES ---
use App\Models\Shipment;
use App\Models\ShipmentQuote;
// ---------------------------

interface CourierInterface
{
    public function getCarrierCode(): string;
    public function getRates(array $shipmentData): array;
    public function createConsignment(Shipment $shipment, ShipmentQuote $quote): array;
    public function trackShipment(string $trackingNumber): array;
}