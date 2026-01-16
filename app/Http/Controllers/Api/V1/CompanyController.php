<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AbnLookupService;

class CompanyController extends Controller
{
    protected $abnService;

    public function __construct(AbnLookupService $abnService)
    {
        $this->abnService = $abnService;
    }

    /**
     * Get the current user's company profile.
     */
    public function show(Request $request)
    {
        // Security: The relationship ensures we only get the user's own company
        return response()->json([
            'data' => $request->user()->company
        ]);
    }

    /**
     * Update company details.
     */
    public function update(Request $request)
    {
        $user = $request->user();

        // Security: Only Admins can update company settings
        if (!$user->isCompanyAdmin()) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $validated = $request->validate([
            'trading_name' => 'nullable|string|max:255',
            'address_line_1' => 'nullable|string|max:255',
            'suburb' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:50',
            'postcode' => 'nullable|string|max:20',
            'billing_email' => 'required|email',
        ]);

        $user->company->update($validated);

        return response()->json([
            'message' => 'Company profile updated successfully.',
            'data' => $user->company
        ]);
    }

    /**
     * Verify ABN on demand (for settings page).
     */
    /**
     * Verify ABN on demand.
     * Route: GET /api/v1/abn-lookup/{abn}
     */
    public function verifyAbn(string $abn) // FIX: Accept string argument directly from Route
    {
        // 1. Pass the route parameter directly to the service
        // (The service already handles sanitization and validation)
        $result = $this->abnService->lookup($abn);

        // 2. Check logic
        if (!$result || $result['status'] !== 'Active') {
            return response()->json(['message' => 'Invalid or inactive ABN.'], 422);
        }

        return response()->json(['data' => $result]);
    }
}