<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserIntegration;
use App\Services\Integrations\ShopifyService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http; // <--- Needed for the API call
use Illuminate\Support\Facades\Log;

class IntegrationController extends Controller
{
    /**
     * List all integrations for the current company
     */
    public function index()
    {
        return response()->json(
            UserIntegration::where('company_id', Auth::user()->company_id)
                ->orderBy('created_at', 'desc')
                ->get()
        );
    }

    /**
     * Manual Connection (Legacy/Alternative)
     */
    public function store(Request $request)
    {
        $request->validate([
            'store_url' => 'required|string',
            'api_secret' => 'required|string',
        ]);

        $integration = UserIntegration::updateOrCreate(
            [
                'company_id' => Auth::user()->company_id, 
                'platform' => 'shopify',
                'store_url' => $request->store_url
            ],
            [
                'user_id' => Auth::id(),
                'api_secret' => $request->api_secret,
                'is_active' => true
            ]
        );

        // Validate immediately
        try {
            $service = new ShopifyService($integration);
            if (!$service->validateConnection()) {
                $integration->delete(); 
                return response()->json(['message' => 'Connection Failed. Check your URL or Token.'], 422);
            }
        } catch (\Exception $e) {
            $integration->delete();
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Shopify Connected Successfully!']);
    }

    /**
     * Sync Orders Manually
     */
    public function sync($id)
    {
        $integration = UserIntegration::where('company_id', Auth::user()->company_id)
            ->findOrFail($id);
        
        try {
            $service = new ShopifyService($integration);
            $count = $service->importOrders();
            return response()->json(['message' => "Imported {$count} new orders."]);
        } catch (\Exception $e) {
            Log::error("Sync Failed", ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Sync Failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove/Disconnect an integration
     * ENTERPRISE UPDATE: Uninstalls the app from Shopify as well.
     */
    public function destroy($id)
    {
        $integration = UserIntegration::where('company_id', Auth::user()->company_id)
            ->findOrFail($id);

        // 1. If it is Shopify, tell them to uninstall us
        if ($integration->platform === 'shopify' && $integration->api_secret) {
            try {
                $shopDomain = $integration->store_url;
                // Ensure domain format is clean
                $shopDomain = preg_replace('#^https?://#', '', $shopDomain);
                $shopDomain = rtrim($shopDomain, '/');

                Log::info("Uninstalling app from Shopify Store: {$shopDomain}");

                $response = Http::withHeaders([
                    'X-Shopify-Access-Token' => $integration->api_secret,
                ])->delete("https://{$shopDomain}/admin/api/2024-01/api_permissions/current.json");

                if ($response->successful()) {
                    Log::info("Successfully uninstalled from Shopify: {$shopDomain}");
                } else {
                    Log::warning("Failed to uninstall from Shopify (might already be removed): " . $response->body());
                }

            } catch (\Exception $e) {
                // We do not stop the local deletion if the remote one fails (e.g. internet down)
                Log::error("Error uninstalling from Shopify: " . $e->getMessage());
            }
        }

        // 2. Delete Local Record
        $integration->delete();

        return response()->json(['message' => 'Integration disconnected and uninstalled successfully.']);
    }
}