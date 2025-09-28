<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class OrchestrationController extends Controller
{
    public function run(Request $request): JsonResponse
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'user' => 'required|string',
            'apis' => 'required|array|min:1',
            'apis.*.url' => 'required|url',
            'apis.*.method' => 'required|string|in:GET,POST,PUT,DELETE,PATCH',
            'apis.*.headers' => 'sometimes|array',
            'apis.*.body' => 'sometimes|array',
            'timeout' => 'sometimes|integer|min:1|max:300'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->input('user');
        $apis = $request->input('apis');
        $timeout = $request->input('timeout', 30); // Default 30 seconds

        Log::info("API Orchestration started for user: {$user}", [
            'api_count' => count($apis),
            'timeout' => $timeout
        ]);

        $results = [];
        $startTime = microtime(true);

        foreach ($apis as $index => $api) {
            $apiStartTime = microtime(true);
            
            try {
                // Prepare HTTP client with timeout
                $httpClient = Http::timeout($timeout);
                
                // Add headers if provided
                if (isset($api['headers']) && is_array($api['headers'])) {
                    $httpClient = $httpClient->withHeaders($api['headers']);
                }

                // Make the API call based on method
                $response = match(strtoupper($api['method'])) {
                    'GET' => $httpClient->get($api['url']),
                    'POST' => $httpClient->post($api['url'], $api['body'] ?? []),
                    'PUT' => $httpClient->put($api['url'], $api['body'] ?? []),
                    'DELETE' => $httpClient->delete($api['url']),
                    'PATCH' => $httpClient->patch($api['url'], $api['body'] ?? []),
                    default => throw new \InvalidArgumentException("Unsupported HTTP method: {$api['method']}")
                };

                $apiEndTime = microtime(true);
                $apiDuration = round(($apiEndTime - $apiStartTime) * 1000, 2); // Convert to milliseconds

                $results[] = [
                    'index' => $index,
                    'url' => $api['url'],
                    'method' => strtoupper($api['method']),
                    'success' => $response->successful(),
                    'status_code' => $response->status(),
                    'response_time_ms' => $apiDuration,
                    'data' => $response->json() ?? $response->body(),
                    'headers' => $response->headers()
                ];

                Log::info("API call completed", [
                    'user' => $user,
                    'url' => $api['url'],
                    'method' => $api['method'],
                    'status' => $response->status(),
                    'duration_ms' => $apiDuration
                ]);

            } catch (\Exception $e) {
                $apiEndTime = microtime(true);
                $apiDuration = round(($apiEndTime - $apiStartTime) * 1000, 2);

                $results[] = [
                    'index' => $index,
                    'url' => $api['url'],
                    'method' => strtoupper($api['method']),
                    'success' => false,
                    'status_code' => 0,
                    'response_time_ms' => $apiDuration,
                    'error' => $e->getMessage(),
                    'data' => null
                ];

                Log::error("API call failed", [
                    'user' => $user,
                    'url' => $api['url'],
                    'method' => $api['method'],
                    'error' => $e->getMessage(),
                    'duration_ms' => $apiDuration
                ]);
            }
        }

        $endTime = microtime(true);
        $totalDuration = round(($endTime - $startTime) * 1000, 2);

        $successCount = collect($results)->where('success', true)->count();
        $failureCount = count($results) - $successCount;

        $response = [
            'success' => true,
            'message' => 'API orchestration completed',
            'user' => $user,
            'summary' => [
                'total_apis' => count($apis),
                'successful' => $successCount,
                'failed' => $failureCount,
                'total_duration_ms' => $totalDuration
            ],
            'results' => $results,
            'timestamp' => now()->toISOString()
        ];

        Log::info("API Orchestration completed for user: {$user}", [
            'total_apis' => count($apis),
            'successful' => $successCount,
            'failed' => $failureCount,
            'total_duration_ms' => $totalDuration
        ]);

        return response()->json($response);
    }
}