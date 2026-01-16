<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AdminController;
use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\TeamController;
use App\Http\Controllers\Api\V1\RateController;
use App\Http\Controllers\Api\V1\ShipmentController;
use App\Http\Controllers\Api\V1\PromoController;
use App\Http\Controllers\Api\V1\SupportController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\IntegrationController;
use App\Http\Controllers\Api\V1\ShopifyAuthController;

// Middleware
use App\Http\Middleware\EnsureSuperAdmin;

Route::prefix('v1')->group(function () {

    // 1. PUBLIC ROUTES
    Route::controller(AuthController::class)->group(function () {
        Route::post('/register', 'register');
        Route::post('/login', 'login')->name('login');
    });
    Route::get('/abn-lookup/{abn}', [CompanyController::class, 'verifyAbn']);

    // 2. PROTECTED ROUTES
    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', fn(Request $request) => $request->user());

        // Company & Team
        Route::controller(CompanyController::class)->group(function () {
            Route::get('/company', 'show');
            Route::put('/company', 'update');
        });
        Route::controller(TeamController::class)->group(function () {
            Route::get('/team', 'index');
            Route::post('/team', 'store');
        });

        // Shipments
        Route::post('/rates', [RateController::class, 'store']);
        Route::controller(ShipmentController::class)->group(function () {
            Route::get('/shipments', 'index');
            Route::get('/shipments/{shipment}', 'show');
            Route::post('/shipments/{shipment}/book', 'book');
            Route::get('/shipments/{shipment}/label', 'downloadLabel')->name('api.v1.shipments.label');
        });
        Route::post('/promotions/validate', [PromoController::class, 'validateCode']);

        // --- INTEGRATIONS ---
        Route::post('/shopify/install', [ShopifyAuthController::class, 'install']);
        Route::get('/integrations', [IntegrationController::class, 'index']);
        Route::post('/integrations', [IntegrationController::class, 'store']);
        Route::post('/integrations/{id}/sync', [IntegrationController::class, 'sync']);
        Route::delete('/integrations/{id}', [IntegrationController::class, 'destroy']); // <--- NEW

        // Support & Notifications
        Route::controller(SupportController::class)->group(function () {
            Route::get('/support', 'index');
            Route::post('/support', 'store');
            Route::get('/support/{id}', 'show');
            Route::post('/support/{id}/reply', 'reply');
        });
        Route::controller(NotificationController::class)->group(function () {
            Route::get('/notifications', 'index');
            Route::post('/notifications/read-all', 'markAllRead');
            Route::post('/notifications/{id}/read', 'markRead');
        });
    });


    // ==============================================================================
    // 3. SUPER ADMIN ROUTES
    // ==============================================================================
    Route::middleware(['auth:sanctum', EnsureSuperAdmin::class])->prefix('admin')->group(function () {

        Route::get('/stats', [AdminController::class, 'stats']);

        // Company Management
        Route::controller(AdminController::class)->group(function () {
            Route::get('/companies', 'companies');
            Route::get('/admin/companies-list', 'companiesList');
            Route::put('/companies/{company}', 'updateCompany');
            Route::post('/companies/{company}/top-up', 'topUpWallet');
            Route::post('/companies/{company}/toggle-status', 'toggleStatus');
            Route::get('/companies/{company}/shipments', 'companyShipments');

            // Company Users
            Route::get('/companies/{company}/users', 'companyUsers');
            Route::post('/companies/{company}/users', 'storeCompanyUser');
        });

        // Carrier Management
        Route::controller(AdminController::class)->group(function () {
            Route::get('/carriers', 'carriers');
            Route::post('/carriers/{carrier}/toggle', 'toggleCarrier');
            Route::put('/carriers/{carrier}', 'updateCarrier');
        });

        // User Management
        Route::get('/users', [AdminController::class, 'users']);
        Route::delete('/users/{user}', [AdminController::class, 'deleteUser']);

        // Shipment Management
        Route::get('/shipments', [AdminController::class, 'shipments']);
        Route::get('/shipments/{id}', [AdminController::class, 'showShipment']);

        // Promotions
        Route::controller(PromoController::class)->group(function () {
            Route::get('/promotions', 'index');
            Route::post('/promotions', 'store');
            Route::post('/promotions/{promotion}/toggle', 'toggle');
        });
    });

});