<?php

namespace Database\Seeders;

use App\Models\CourierMapping;
use App\Models\CarrierConfig;
use Illuminate\Database\Seeder;

class CourierMappingSeeder extends Seeder
{
    public function run(): void
    {
        // =========================================================
        // PART 1: CARRIER IDENTITY MAPPINGS
        // This fixes the "Booking failed: courier config not found" error.
        // It links the name "Hunter Express" to the correct config ID.
        // =========================================================
        
        $hunterConfig = CarrierConfig::where('carrier_code', 'hunters')->first();
        $cpConfig = CarrierConfig::where('carrier_code', 'couriers_please')->first();
        $tntConfig = CarrierConfig::where('carrier_code', 'tnt')->first();

        // 1. Hunter Express
        if ($hunterConfig) {
            CourierMapping::updateOrCreate(
                ['name_in_app' => 'Hunter Express'],
                [
                    'carrier_config_id' => $hunterConfig->id,
                    'courier_code' => 'hunters',
                    'is_active' => true
                ]
            );
        }

        // 2. Couriers Please
        if ($cpConfig) {
            CourierMapping::updateOrCreate(
                ['name_in_app' => 'Couriers Please'],
                [
                    'carrier_config_id' => $cpConfig->id,
                    'courier_code' => 'couriers_please',
                    'is_active' => true
                ]
            );
        }

        // 3. TNT / FedEx
        if ($tntConfig) {
            CourierMapping::updateOrCreate(
                ['name_in_app' => 'TNT / FedEx'],
                [
                    'carrier_config_id' => $tntConfig->id,
                    'courier_code' => 'tnt',
                    'is_active' => true
                ]
            );
        }

        // =========================================================
        // PART 2: ITEM TYPE MAPPINGS (Your Existing Logic)
        // This ensures "box" becomes "CTN" when sending to Hunter.
        // =========================================================

        $huntersItems = [
            // User Input      => [Code, Description]
            'carton'        => ['CTN', 'Carton'],
            'box'           => ['CTN', 'Carton'], 
            'crate'         => ['CRT', 'Crate'],
            'other'         => ['OTH', 'Other'],
            'pallet'        => ['PLT', 'Pallet'],
            'pallet - half' => ['HPL', 'Pallet - Half'], 
            'half pallet'   => ['HPL', 'Pallet - Half'],
            'roll'          => ['ROL', 'Roll/Tube'],
            'tube'          => ['ROL', 'Roll/Tube'],
            'satchel'       => ['SAT', 'Satchel'],
            'skid'          => ['SKD', 'Skid'],
        ];

        foreach ($huntersItems as $input => $output) {
            CourierMapping::updateOrCreate(
                [
                    'courier_code' => 'hunters',
                    'target_field' => 'item_type',
                    'input_value' => $input
                ],
                [
                    'output_code' => $output[0],
                    'output_description' => $output[1],
                    // We attach the config ID here too for consistency
                    'carrier_config_id' => $hunterConfig?->id
                ]
            );
        }
    }
}