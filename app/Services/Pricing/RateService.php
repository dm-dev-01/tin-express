<?php

namespace App\Services\Pricing;

use App\Services\Couriers\CourierStrategyFactory;
use Illuminate\Support\Facades\Log;

class RateService
{
    protected CourierStrategyFactory $factory;

    public function __construct(CourierStrategyFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Get rates from all active couriers.
     *
     * @param array $data Validated shipment data
     * @return array Combined list of rates
     */
    public function getRates(array $data): array
    {
        $allRates = [];
        
        // 1. Get all active courier strategies from your DB configuration
        $strategies = $this->factory->getActiveStrategies();

        // 2. Loop through each courier and fetch their rates
        foreach ($strategies as $courier) {
            try {
                // Each strategy knows how to talk to its specific API
                $quotes = $courier->getRates($data);
                
                foreach ($quotes as $quote) {
                    // Optional: Add a profit margin here (e.g., 20%)
                    // $quote['price_cents'] = (int)($quote['price_cents'] * 1.20);
                    
                    $allRates[] = $quote;
                }
                
            } catch (\Exception $e) {
                // If one courier fails (e.g. API down), log it but don't crash the whole page
                Log::error("Failed to get rates from " . $courier->getCarrierCode(), [
                    'error' => $e->getMessage()
                ]);
            }
        }

        $uniqueRates = [];
        $seen = [];

        foreach ($allRates as $rate) {
            $key = $rate['courier_name'] . '_' . ($rate['service_code'] ?? $rate['service_name']);
            if (!in_array($key, $seen)) {
                $uniqueRates[] = $rate;
                $seen[] = $key;
            }
        }
        $allRates = $uniqueRates;
        // ------------------------------

        // 3. Sort by cheapest price first
        usort($allRates, function ($a, $b) {
            return $a['price_cents'] <=> $b['price_cents'];
        });

        return $allRates;
    }
}