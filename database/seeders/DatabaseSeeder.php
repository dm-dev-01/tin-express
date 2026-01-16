<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ==========================================
        // 1. SUPER ADMIN SETUP (Safe Create)
        // ==========================================
        $adminEmail = 'danish@danish.com';
        
        $hq = Company::firstOrCreate(
            ['billing_email' => $adminEmail],
            [
                'entity_name'    => 'TinExpress HQ',
                'abn'            => '00000000000',
                'abn_status'     => 'Active',
                'wallet_balance' => 0,
            ]
        );

        if (!User::where('email', $adminEmail)->exists()) {
            User::create([
                'company_id' => $hq->id,
                'first_name' => 'Danish',
                'last_name'  => 'Ali',
                'email'      => $adminEmail,
                'password'   => Hash::make('123456789'),
                'role'       => 'super_admin',
                'email_verified_at' => now(),
            ]);
            $this->command->info('Super Admin created.');
        }

        // ==========================================
        // 2. STANDARD COMPANY ADMIN (Safe Create)
        // ==========================================
        $companyEmail = 'admin@demo.com';

        $demoCompany = Company::firstOrCreate(
            ['billing_email' => $companyEmail],
            [
                'entity_name'    => 'Demo Logistics Pty Ltd',
                'abn'            => '12345678901',
                'abn_status'     => 'Active',
                'wallet_balance' => 0,
            ]
        );

        if (!User::where('email', $companyEmail)->exists()) {
            User::create([
                'company_id' => $demoCompany->id,
                'first_name' => 'Test',
                'last_name'  => 'User',
                'email'      => $companyEmail,
                'password'   => Hash::make('password'),
                'role'       => User::ROLE_COMPANY_ADMIN, // Ensure this constant exists in User model
            ]);
            $this->command->info('Standard Company Admin created.');
        }

        // ==========================================
        // 3. CARRIER CONFIGURATIONS (PROTECTED)
        // ==========================================
        // We ONLY run this if the table is empty. 
        if (DB::table('carrier_configs')->count() === 0) {
            $this->call([
                CarrierConfigSeeder::class,
                CourierMappingSeeder::class,
            ]);
            $this->command->info('Carrier Configs seeded.');
        } else {
            $this->command->warn('Carrier Configs already exist. Skipped to protect data.');
        }

        // ==========================================
        // 4. DEMO DATA (Safe Populate)
        // ==========================================
        if (Company::count() < 5) {
            $this->seedDemoData();
            $this->command->info('Demo Data seeded.');
        } else {
            $this->command->warn('Demo Data already exists. Skipped.');
        }
    }

    /**
     * Helper to seed demo content without cluttering the run method
     */
    private function seedDemoData()
    {
        $companies = [
            ['Alpha Logistics', 'Active'],
            ['Beta Freight', 'Active'],
            ['Gamma Shipping', 'Suspended'],
            ['Delta Distributions', 'Active'],
            ['Omega Transports', 'Active'],
        ];

        foreach ($companies as [$name, $status]) {
            $email = strtolower(str_replace(' ', '', $name)) . '@demo.com';
            
            if (Company::where('billing_email', $email)->exists()) continue;

            $companyId = DB::table('companies')->insertGetId([
                'entity_name'    => $name,
                'abn'            => rand(10000000000, 99999999999),
                'abn_status'     => $status,
                'billing_email'  => $email,
                'wallet_balance' => rand(100, 5000), 
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            DB::table('users')->insert([
                'company_id' => $companyId,
                'first_name' => 'Admin',
                'last_name'  => $name,
                'email'      => $email,
                'password'   => Hash::make('password'),
                'role'       => 'company_admin',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}