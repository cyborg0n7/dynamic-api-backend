<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ApiDefinition;
use App\Models\RequestLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

class ApiGatewayController extends Controller
{
    public function handle(Request $request)
    {
        $start = microtime(true);

        $path = $request->path();
        $method = $request->method();

        $api = ApiDefinition::where('endpoint', $path)
            ->where('method', $method)
            ->first();

        if (!$api) {
            return response()->json([
                'error' => [
                    'code' => 404,
                    'message' => 'API not found'
                ]
            ], 404);
        }

        // Authentication check
        if ($api->auth_type) {
            $authResponse = $this->checkAuth($request);
            if ($authResponse) return $authResponse;
        }

        // Rate limiting
        $rateLimitResponse = $this->checkRateLimit($request);
        if ($rateLimitResponse) return $rateLimitResponse;

        // Forward request
        $response = Http::withHeaders($request->headers->all())
            ->send($method, $api->target_url, [
                'query' => $request->query(),
                'json' => $request->all()
            ]);

        $end = microtime(true);

        // Logging
        RequestLog::create([
            'user' => $request->header('x-api-key') ?? null,
            'endpoint' => $path,
            'status' => $response->status(),
            'latency' => ($end - $start) * 1000
        ]);

        return response($response->body(), $response->status());
    }

    private function checkAuth($request)
    {
        $apiKey = $request->header('x-api-key');
        if (!$apiKey) {
            return response()->json(['error' => ['code' => 401, 'message' => 'Missing API key']], 401);
        }

        if (!\App\Models\ApiKey::where('key', $apiKey)->exists()) {
            return response()->json(['error' => ['code' => 403, 'message' => 'Invalid API key']], 403);
        }

        return null;
    }

    private function checkRateLimit($request)
    {
        // Temporarily disabled rate limiting due to Redis not being available
        return null;
        
        $apiKey = $request->header('x-api-key');
        if (!$apiKey) return null;

        $key = "rate_limit:" . $apiKey;
        $count = Redis::incr($key);

        if ($count == 1) Redis::expire($key, 60);

        if ($count > 100) {
            return response()->json(['error' => ['code' => 429, 'message' => 'Rate limit exceeded']], 429);
        }
        return null;
    }
}
