<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class TeamController extends Controller
{
    // List all employees in MY company
    public function index(Request $request)
    {
        $currentUser = $request->user();

        // Security Check: Only admins can see the list
        if (!$currentUser->isCompanyAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $employees = User::where('company_id', $currentUser->company_id)
            ->orderBy('created_at', 'desc')
            ->get(['id', 'first_name', 'last_name', 'email', 'role', 'created_at']);

        return response()->json(['data' => $employees]);
    }

    // Add a new employee
    public function store(Request $request)
    {
        $currentUser = $request->user();

        if (!$currentUser->isCompanyAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => ['required', 'email', 'unique:users,email'], // Email must be unique globally
            'password'   => 'required|string|min:8',
        ]);

        $user = User::create([
            'company_id' => $currentUser->company_id, // Link to SAME company
            'first_name' => $validated['first_name'],
            'last_name'  => $validated['last_name'],
            'email'      => $validated['email'],
            'password'   => Hash::make($validated['password']),
            'role'       => User::ROLE_COMPANY_USER, // Default to standard user
        ]);

        return response()->json([
            'message' => 'Team member added successfully',
            'user' => $user
        ], 201);
    }
}