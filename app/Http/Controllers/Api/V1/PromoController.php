<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use Illuminate\Http\Request;

class PromoController extends Controller
{
    // ADMIN: List
    public function index() {
        return response()->json(Promotion::latest()->get());
    }

    // ADMIN: Create
    public function store(Request $request) {
        $validated = $request->validate([
            'code' => 'required|unique:promotions,code|uppercase|alpha_num',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'max_uses' => 'nullable|integer',
            'min_spend' => 'nullable|numeric', // Input in Dollars
            'expires_at' => 'nullable|date',
        ]);

        $promo = Promotion::create([
            'code' => $validated['code'],
            'type' => $validated['type'],
            'value' => $validated['value'],
            'max_uses' => $validated['max_uses'],
            'min_spend_cents' => $validated['min_spend'] ? $validated['min_spend'] * 100 : null,
            'expires_at' => $validated['expires_at'],
        ]);

        return response()->json($promo);
    }

    // ADMIN: Toggle
    public function toggle(Promotion $promotion) {
        $promotion->update(['is_active' => !$promotion->is_active]);
        return response()->json(['message' => 'Updated']);
    }

    // USER: Validate Code
    public function validateCode(Request $request) {
        $request->validate(['code' => 'required', 'amount_cents' => 'required|integer']);
        
        $promo = Promotion::where('code', $request->code)->first();
        
        if (!$promo || !$promo->isValid($request->amount_cents)) {
            return response()->json(['message' => 'Invalid or expired code'], 422);
        }

        $discount = $promo->calculateDiscount($request->amount_cents);

        return response()->json([
            'promo_id' => $promo->id,
            'code' => $promo->code,
            'discount_cents' => $discount,
            'final_price_cents' => $request->amount_cents - $discount
        ]);
    }
}