<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Company;
use App\Models\User;
use App\Services\AbnLookupService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\HasApiTokens;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, AbnLookupService $abnService)
    {
        $abnDetails = $abnService->lookup($request->abn);
        
        // Strict ABN validation
        if (!$abnDetails || $abnDetails['status'] !== 'Active') {
            return response()->json(['message' => 'Invalid ABN'], 422);
        }

        $user = DB::transaction(function () use ($request, $abnDetails) {
            $company = Company::create([
                'entity_name' => $abnDetails['entity_name'],
                'abn' => $request->abn,
                'abn_status' => $abnDetails['status'],
                'billing_email' => $request->email,
            ]);

            return User::create([
                'company_id' => $company->id,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => User::ROLE_COMPANY_ADMIN, 
            ]);
        });

        event(new Registered($user));
        
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful.',
            'access_token' => $token,
            'user' => $user
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid login details'
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        
        // Delete old tokens to keep session clean
        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user // <--- CRITICAL: Sending User Data to Frontend
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }
}