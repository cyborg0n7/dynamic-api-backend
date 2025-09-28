<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    protected $middleware = [
        // global middleware
    ];

    protected $middlewareGroups = [
        'web' => [
            // web middleware
        ],
        'api' => [
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            'auth:api',
        ],
    ];

    protected $routeMiddleware = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'apikey' => \App\Http\Middleware\ApiKeyAuth::class,
        'role' => \App\Http\Middleware\RoleMiddleware::class,
    ];

}
