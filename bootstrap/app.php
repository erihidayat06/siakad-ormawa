<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// bootstrap/app.php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Matikan CSRF untuk route tertentu agar tidak ada 401
        $middleware->validateCsrfTokens(except: [
            'proposals/*', // Bypass untuk semua route di bawah proposals
            'admin/*',     // Atau sesuaikan dengan url filament Anda
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
