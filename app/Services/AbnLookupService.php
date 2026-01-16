<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AbnLookupService
{
    protected string $baseUrl = 'https://abr.business.gov.au/json/AbnDetails.aspx';

    /**
     * Lookup an ABN using the Official ABR API.
     */
    public function lookup(string $abn): ?array
    {
        // 1. Sanitize input
        $abn = preg_replace('/[^0-9]/', '', $abn);

        if (strlen($abn) !== 11) {
            return null;
        }

        // 2. Get API Key from .env
        $apiKey = config('services.abr.guid');

        // Fallback for Dev without Key: Return Mock if Key is missing
        if (empty($apiKey)) {
            Log::warning('ABN Lookup: No API Key found. Using mock data.');
            return $this->getMockData($abn);
        }

        try {
            // 3. Call the API
            $response = Http::get($this->baseUrl, [
                'abn' => $abn,
                'guid' => $apiKey,
                'callback' => 'callback' // ABR requires a callback param
            ]);

            if ($response->failed()) {
                Log::error('ABR API Error: ' . $response->status());
                return null;
            }

            // 4. Parse JSONP (The API returns `callback({...})`)
            $body = $response->body();
            $jsonString = $this->stripCallback($body);
            $data = json_decode($jsonString, true);

            // 5. Validate Response
            if (isset($data['Message']) && !empty($data['Message'])) {
                // API returns "Message" string if ABN is invalid
                return null;
            }

            // 6. Map to our App's Format
            return [
                'abn' => $data['Abn'] ?? $abn,
                'entity_name' => $data['EntityName'] ?? 'Unknown Entity',
                'status' => $data['AbnStatus'] ?? 'Active',
                'state' => $data['AddressState'] ?? '',
                'postcode' => $data['AddressPostcode'] ?? '',
                'trading_name' => $data['BusinessName'][0] ?? null // Grab first trading name if exists
            ];

        } catch (\Exception $e) {
            Log::error('ABN Lookup Exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Helper to clean JSONP response
     */
    private function stripCallback(string $responseBody): string
    {
        // Remove "callback(" from start and ")" from end
        $json = preg_replace('/^callback\((.*)\)$/s', '$1', $responseBody);
        return $json;
    }

    private function getMockData($abn): array
    {
        return [
            'abn' => $abn,
            'entity_name' => 'Mock Enterprise Pty Ltd',
            'status' => 'Active',
            'postcode' => '2000',
            'state' => 'NSW'
        ];
    }
}