<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\UserIntegration;
use App\Models\User;

class ShopifyAuthController extends Controller
{
    /**
     * 1. REDIRECT: User clicks "Connect", we send them to Shopify
     * Enterprise Change: Use Cache instead of Session for state persistence.
     */
    public function install(Request $request)
    {
        Log::info("Shopify Install Started.");

        $request->validate(['shop' => 'required|string']);

        // A. Capture User Context
        $user = $request->user();
        if (!$user) {
            Log::error("Shopify Install Failed: User is NULL/Unauthorized.");
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Clean Shop URL to standard format (store.myshopify.com)
        $shop = $request->input('shop');
        $shop = preg_replace('#^https?://#', '', $shop);
        $shop = rtrim($shop, '/');
        if (!str_contains($shop, '.')) {
            $shop .= '.myshopify.com';
        }

        // B. Generate Secure State
        // We create a random token that serves as the key to our data
        $state = Str::random(40);

        // C. Store User Context in Server Cache (TTL: 10 minutes)
        // Key: 'shopify_oauth_{state}' => Value: {user_id}
        Cache::put('shopify_oauth_' . $state, $user->id, 600);

        // Configuration
        $clientId = config('services.shopify.key');
        $redirectUri = config('services.shopify.redirect');
        
        // Scopes: strictly what is needed for fulfillment and order sync
        $scopes = 'read_orders,write_fulfillments,read_locations,read_merchant_managed_fulfillment_orders,write_merchant_managed_fulfillment_orders,write_assigned_fulfillment_orders';

        $installUrl = "https://{$shop}/admin/oauth/authorize?client_id={$clientId}&scope={$scopes}&redirect_uri={$redirectUri}&state={$state}";

        return response()->json(['url' => $installUrl]);
    }

    /**
     * 2. CALLBACK: Shopify sends the user back with a Code
     */
    public function callback(Request $request)
    {
        Log::info("Shopify Callback Hit.", $request->all());

        $state = $request->input('state');
        $shop = $request->input('shop');
        $code = $request->input('code');

        // A. Validation: Check if state exists in Cache
        // Cache::pull retrieves the item AND deletes it immediately (prevents Replay Attacks)
        $userId = Cache::pull('shopify_oauth_' . $state);

        if (!$userId) {
            Log::error("Shopify OAuth Security Error: Invalid or Expired State", ['state' => $state]);
            return response()->json(['error' => 'Invalid Session State or Timeout. Please try again.'], 403);
        }

        // B. Restore User Context
        $user = User::find($userId);
        if (!$user) {
            Log::error("Shopify OAuth Error: User ID from cache not found in DB.", ['id' => $userId]);
            return response()->json(['error' => 'User not found.'], 404);
        }

        // C. Exchange Code for Access Token
        $response = Http::post("https://{$shop}/admin/oauth/access_token", [
            'client_id' => config('services.shopify.key'),
            'client_secret' => config('services.shopify.secret'),
            'code' => $code,
        ]);

        if ($response->failed()) {
            Log::error("Shopify OAuth Failed: " . $response->body());
            return response()->json(['error' => 'Authorization Failed from Shopify'], 500);
        }

        $accessToken = $response->json()['access_token'];

        // D. Save Integration
        // We use updateOrCreate so users can re-connect/refresh tokens easily
        UserIntegration::updateOrCreate(
            [
                'company_id' => $user->company_id,
                'platform' => 'shopify',
                'store_url' => $shop
            ],
            [
                'user_id' => $user->id,
                'api_secret' => $accessToken, 
                'api_key' => config('services.shopify.key'),
                'is_active' => true,
                'last_synced_at' => now()
            ]
        );

        Log::info("Shopify Connected Successfully for User: " . $user->name);

        // E. Redirect back to Frontend
        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
        return redirect()->to($frontendUrl . '/dashboard/integrations?success=true');
    }
}