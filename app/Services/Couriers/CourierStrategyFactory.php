<?php

declare(strict_types=1);

namespace App\Services\Couriers;

use App\Models\CarrierConfig;
use App\Services\Couriers\Strategy\CouriersPleaseStrategy;
use App\Services\Couriers\Strategy\HunterExpressStrategy;
use App\Services\Couriers\Strategy\MockCourierService;
use App\Services\Couriers\Strategy\TNTStrategy;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class CourierStrategyFactory
{
    /**
     * returns a list of instantiated CourierInterface strategies
     * based on active database records.
     *
     * @return array<CourierInterface>
     */
    public function getActiveStrategies(): array
    {
        $configs = CarrierConfig::where('is_active', true)->get();
        $strategies = [];

        foreach ($configs as $config) {
            try {
                $strategy = $this->createStrategy($config);
                if ($strategy) {
                    $strategies[] = $strategy;
                }
            } catch (\Throwable $e) { // <--- CHANGE THIS from \Exception to \Throwable
                // This will catch "Method not found" fatal errors and keep the app running
                Log::error("Failed to initialize courier strategy: {$config->carrier_code}", [
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $strategies;
    }

    public function createStrategy(CarrierConfig $config): ?CourierInterface
    {
        return match ($config->carrier_code) {
            'mock_express' => new MockCourierService(),
            
            'hunters' => new HunterExpressStrategy(
        (string) $config->api_key,       // Token: XXLFNZRLPW
        (string) $config->account_code,  // TV Number: 55010
        (string) ($config->extra_settings['customer_code'] ?? 'APITEST') // <--- New Argument
    ),

            'couriers_please' => new CouriersPleaseStrategy(
                (string) $config->account_code, // WD00000006
                (string) $config->api_key,      // cc995...
                $config->environment === 'test' // true
            ),

            'tnt' => new TNTStrategy(
                // 1. Username (stored in api_key)
                (string) $config->api_key,    
                // 2. Password (stored in api_secret)
                (string) $config->api_secret, 
                // 3. Account Number (stored in account_code)
                (string) $config->account_code, 
                // 4. Test Mode Flag (Force UAT if not production)
                $config->environment !== 'production', 
                // 5. Extra Settings
                (array) ($config->extra_settings ?? [])
            ),
            default => null,
        };
    }
}