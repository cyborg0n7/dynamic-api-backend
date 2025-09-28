<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ApiKey;

class ApiKeyAuth
{
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->header('x-api-key');

        if (!$apiKey) {
            return response()->json(['message' => 'API key missing'], 401);
        }

        $hashedKey = hash('sha256', $apiKey);

        if (!ApiKey::where('key', $hashedKey)->exists()) {
            return response()->json(['message' => 'Invalid API key'], 401);
        }

        return $next($request);
    }
}
