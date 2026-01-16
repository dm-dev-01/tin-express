<?php

namespace Database\Seeders;

use App\Models\CarrierConfig;
use Illuminate\Database\Seeder;

class CarrierConfigSeeder extends Seeder
{
    public function run(): void
    {
        // 1. MOCK EXPRESS
        CarrierConfig::create([
            'carrier_code' => 'mock_express',
            'account_code' => 'TEST_ACC',
            'api_key'      => 'TEST_KEY',
            'environment'  => 'test',
            'is_active'    => true,
            'extra_settings' => ['label_format' => 'PDF'],
        ]);

        // 2. HUNTER EXPRESS
        CarrierConfig::create([
            'carrier_code' => 'hunters',
            'account_code' => '55010',
            'api_key'      => 'XXLFNZRLPW',
            'environment'  => 'test',
            'is_active'    => true,
            'extra_settings' => [
                'customer_code' => 'APITEST',
                'label_format' => 'PDF'
            ],
        ]);

        // 3. COURIERS PLEASE (Updated with provided credentials)
        CarrierConfig::create([
            'carrier_code' => 'couriers_please',
            'account_code' => 'WD00000006',
            'api_key'      => 'cc995cb9-38b9-4b40-995a-01f3a427a579',
            'environment'  => 'test',
            'is_active'    => true,
            'extra_settings' => [
                'label_delay_minutes' => 5, // <--- CONFIGURATION, NOT CODE
                'label_format' => 'PDF'
            ],
        ]);
        
        // 4. TNT
        CarrierConfig::create([
            'carrier_code' => 'tnt',
            'account_code' => 'TNT_USER',
            'api_key'      => 'TNT_PASS',
            'api_secret'   => 'TNT_ACC_NUM',
            'environment'  => 'test',
            'is_active'    => false,
        ]);
    }
}