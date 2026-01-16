<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\ShopifyAuthController;


Route::group(['middleware' => ['web']], function () {
    
    // 2. Callback (Redirected from Shopify)
    Route::get('/api/v1/shopify/callback', [ShopifyAuthController::class, 'callback']);

});

// Update this section
Route::get('/{any}', function () {
    return view('dashboard');
})->where('any', '^(?!docs|api|storage|sanctum).*$');