<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

      // 1. TRUST ALL PROXIES (Fixes Ngrok HTTPS detection)
        $middleware->trustProxies(at: '*');

        // 2. FORCE SECURE COOKIES
        $middleware->encryptCookies(except: []);
        
        // 3. EXCLUDE OAUTH ROUTES FROM CSRF (Optional, but good for install endpoint)
        $middleware->validateCsrfTokens(except: [
            '/api/v1/shopify/install'
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
