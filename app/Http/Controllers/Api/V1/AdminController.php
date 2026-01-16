<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CarrierConfig;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function stats()
    {
        return response()->json([
            'total_companies' => Company::count(),
            'total_shipments' => Shipment::count(),
            'total_revenue'   => Shipment::sum('total_price_cents'),
            'active_carriers' => CarrierConfig::where('is_active', true)->count(),
        ]);
    }

    /**
     * FIX: Simplified Companies List
     */
    public function companies(Request $request)
    {
        // Eager load counts to ensure data is populated
        $query = Company::withCount(['users', 'shipments']);

        if ($search = $request->input('search')) {
            $query->where('entity_name', 'like', "%{$search}%")
                  ->orWhere('billing_email', 'like', "%{$search}%");
        }

        // Return standard pagination
        return response()->json($query->latest()->paginate(15));
    }

    // ... (keep topUpWallet and toggleStatus methods as they were) ...
    public function topUpWallet(Request $request, Company $company)
    {
        $request->validate(['amount' => 'required|numeric|min:1']);
        $company->increment('wallet_balance', $request->amount);
        return response()->json(['message' => 'Wallet updated']);
    }

    public function toggleStatus(Company $company)
    {
        $company->abn_status = ($company->abn_status === 'Active') ? 'Suspended' : 'Active';
        $company->save();
        return response()->json(['status' => $company->abn_status]);
    }

    public function updateCompany(Request $request, Company $company)
    {
        $validated = $request->validate([
            'entity_name'   => 'required|string|max:255',
            'billing_email' => 'required|email',
            'abn'           => 'required|string|size:11',
            'abn_status'    => 'required|in:Active,Suspended',
        ]);

        $company->update($validated);

        return response()->json(['message' => 'Company details updated successfully']);
    }

    public function companyShipments(Company $company)
    {
        $shipments = $company->shipments()
            ->latest()
            ->paginate(10); // 10 per page for the modal

        return response()->json($shipments);
    }

    public function carriers()
    {
        // Hide secrets in the list view for security
        return response()->json(CarrierConfig::all()->makeHidden(['api_secret', 'api_key']));
    }

    public function toggleCarrier(CarrierConfig $carrier)
    {
        $carrier->update(['is_active' => !$carrier->is_active]);
        return response()->json(['message' => 'Carrier status updated']);
    }

    /**
     * NEW: Update Carrier Configuration
     */
    public function updateCarrier(Request $request, CarrierConfig $carrier)
    {
        $validated = $request->validate([
            'account_code' => 'nullable|string',
            'api_key'      => 'nullable|string', // Only provided if changing
            'api_secret'   => 'nullable|string', // Only provided if changing
            'environment'  => 'required|in:test,production',
        ]);

        // Security: Remove null/empty keys so we don't overwrite existing encrypted data with blanks
        $dataToUpdate = array_filter($validated, fn($value) => !is_null($value) && $value !== '');

        $carrier->update($dataToUpdate);

        return response()->json(['message' => 'Carrier configuration updated successfully']);
    }

    // ... existing code ...

    /**
     * GLOBAL USERS LIST
     */
    public function users(Request $request)
    {
        $query = \App\Models\User::with('company');

        // Search by Name or Email
        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by Role
        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        return response()->json($query->latest()->paginate(20));
    }

    /**
     * GET USERS FOR A SPECIFIC COMPANY
     */
    public function companyUsers(Company $company)
    {
        return response()->json($company->users()->latest()->get());
    }

    /**
     * CREATE USER FOR COMPANY
     */
    public function storeCompanyUser(Request $request, Company $company)
    {
        $validated = $request->validate([
            'first_name' => 'required|string',
            'last_name'  => 'required|string',
            'email'      => 'required|email|unique:users',
            'password'   => 'required|min:8',
            'role'       => 'required|in:company_admin,company_user'
        ]);

        $user = $company->users()->create([
            'first_name' => $validated['first_name'],
            'last_name'  => $validated['last_name'],
            'email'      => $validated['email'],
            'password'   => \Illuminate\Support\Facades\Hash::make($validated['password']),
            'role'       => $validated['role'],
            'email_verified_at' => now(),
        ]);

        return response()->json(['message' => 'User created successfully', 'user' => $user]);
    }

    /**
     * DELETE USER
     */
    public function deleteUser(\App\Models\User $user)
    {
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'Cannot delete yourself.'], 403);
        }

        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }

    /**
     * GLOBAL SHIPMENTS LIST (With Filters)
     */
    public function shipments(Request $request)
    {
        $query = Shipment::with('company');

        // Filter: Status
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Filter: Courier
        if ($courier = $request->input('courier')) {
            $query->where('courier_name', 'like', "%{$courier}%");
        }

        // Search: Tracking or ID
        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('tracking_number', 'like', "%{$search}%")
                  ->orWhere('id', $search);
            });
        }

        return response()->json($query->latest()->paginate(20));
    }

    /**
     * ADMIN SHIPMENT DETAILS
     */
    public function showShipment($id)
    {
        // Bypassing company policy check for Super Admin
        $shipment = Shipment::with(['items', 'company', 'quotes'])->findOrFail($id);
        return response()->json($shipment);
    }

    public function companiesList() {
    return response()->json(Company::select('id', 'entity_name')->orderBy('entity_name')->get());
}
}
