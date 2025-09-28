<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\RequestLog;
use App\Jobs\OrchestrationJob;

class OrchestrationController extends Controller
{
    /**
     * Execute orchestration - supports both sync and async modes
     */
    public function run(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'user' => 'required|string',
            'apis' => 'required|array|min:1',
            'apis.*.url' => 'required|url',
            'apis.*.method' => 'required|string|in:GET,POST,PUT,DELETE,PATCH',
            'apis.*.headers' => 'sometimes|array',
            'apis.*.body' => 'sometimes|array',
            'apis.*.timeout' => 'sometimes|integer|min:1|max:300',
            'apis.*.conditions' => 'sometimes|array',
            'apis.*.request_transformations' => 'sometimes|array',
            'apis.*.response_transformations' => 'sometimes|array',
            'mode' => 'sometimes|string|in:sync,async',
            'options' => 'sometimes|array',
            'options.stop_on_failure' => 'sometimes|boolean',
            'options.priority' => 'sometimes|string|in:low,normal,high',
            'options.delay' => 'sometimes|integer|min:0'
        ]);

        $user = $request->input('user');
        $apis = $request->input('apis');
        $mode = $request->input('mode', 'sync'); // Default to sync for backward compatibility
        $options = $request->input('options', []);

        // Generate unique orchestration ID
        $orchestrationId = uniqid('orch_' . time() . '_');

        Log::info("API Orchestration requested", [
            'orchestration_id' => $orchestrationId,
            'user' => $user,
            'mode' => $mode,
            'apis_count' => count($apis),
            'options' => $options
        ]);

        if ($mode === 'async') {
            return $this->runAsync($user, $apis, $orchestrationId, $options);
        } else {
            return $this->runSync($user, $apis, $orchestrationId, $options);
        }
    }

    /**
     * Run orchestration asynchronously using queue
     */
    private function runAsync($user, $apis, $orchestrationId, $options)
    {
        try {
            // Determine queue priority
            $priority = match($options['priority'] ?? 'normal') {
                'high' => 10,
                'low' => 1,
                default => 5
            };

            // Create and dispatch the job
            $job = new OrchestrationJob($user, $apis, $orchestrationId, $options);
            
            if (isset($options['delay']) && $options['delay'] > 0) {
                $job->delay(now()->addSeconds($options['delay']));
            }

            dispatch($job->onQueue('orchestration')->priority($priority));

            Log::info("Orchestration job queued", [
                'orchestration_id' => $orchestrationId,
                'priority' => $priority,
                'delay' => $options['delay'] ?? 0
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Orchestration job queued successfully',
                'orchestration_id' => $orchestrationId,
                'mode' => 'async',
                'status_url' => route('orchestration.status', ['id' => $orchestrationId]),
                'estimated_completion' => now()->addSeconds(30 + ($options['delay'] ?? 0))->toISOString()
            ], 202); // 202 Accepted

        } catch (\Exception $e) {
            Log::error("Failed to queue orchestration job", [
                'orchestration_id' => $orchestrationId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to queue orchestration job',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Run orchestration synchronously (original implementation)
     */
    private function runSync($user, $apis, $orchestrationId, $options)
    {
        $results = [];

        Log::info("API Orchestration started (sync)", [
            'orchestration_id' => $orchestrationId,
            'user' => $user,
            'apis_count' => count($apis)
        ]);

        foreach ($apis as $index => $api) {
            $startTime = microtime(true);
            
            try {
                // Prepare HTTP client with timeout
                $timeout = $api['timeout'] ?? 30;
                $httpClient = Http::timeout($timeout);
                
                // Add headers if provided
                if (isset($api['headers']) && is_array($api['headers'])) {
                    $httpClient = $httpClient->withHeaders($api['headers']);
                }

                // Make the API call based on method
                $method = strtoupper($api['method']);
                $response = match($method) {
                    'GET' => $httpClient->get($api['url']),
                    'POST' => $httpClient->post($api['url'], $api['body'] ?? []),
                    'PUT' => $httpClient->put($api['url'], $api['body'] ?? []),
                    'DELETE' => $httpClient->delete($api['url']),
                    'PATCH' => $httpClient->patch($api['url'], $api['body'] ?? []),
                    default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}")
                };

                $endTime = microtime(true);
                $duration = round(($endTime - $startTime) * 1000, 2); // Convert to milliseconds

                $result = [
                    'index' => $index,
                    'url' => $api['url'],
                    'method' => $method,
                    'status_code' => $response->status(),
                    'success' => $response->successful(),
                    'duration_ms' => $duration,
                    'response_size' => strlen($response->body()),
                    'data' => $response->json() ?? $response->body()
                ];

                // Log successful request
                $this->logRequest($api, $result, $user, $orchestrationId);

            } catch (\Illuminate\Http\Client\RequestException $e) {
                $endTime = microtime(true);
                $duration = round(($endTime - $startTime) * 1000, 2);

                $result = [
                    'index' => $index,
                    'url' => $api['url'],
                    'method' => $method,
                    'status_code' => $e->response ? $e->response->status() : 0,
                    'success' => false,
                    'duration_ms' => $duration,
                    'error' => 'HTTP Request failed: ' . $e->getMessage(),
                    'data' => null
                ];

                // Log failed request
                $this->logRequest($api, $result, $user, $orchestrationId);

            } catch (\Exception $e) {
                $endTime = microtime(true);
                $duration = round(($endTime - $startTime) * 1000, 2);

                $result = [
                    'index' => $index,
                    'url' => $api['url'],
                    'method' => $method,
                    'status_code' => 0,
                    'success' => false,
                    'duration_ms' => $duration,
                    'error' => 'Orchestration error: ' . $e->getMessage(),
                    'data' => null
                ];

                Log::error("API Orchestration error for {$api['url']}: " . $e->getMessage());
            }

            $results[] = $result;

            // Stop on failure if option is set
            if (!$result['success'] && ($options['stop_on_failure'] ?? false)) {
                Log::info("Stopping orchestration due to failure", [
                    'orchestration_id' => $orchestrationId,
                    'failed_at_index' => $index
                ]);
                break;
            }
        }

        // Calculate summary statistics
        $totalRequests = count($results);
        $successfulRequests = count(array_filter($results, fn($r) => $r['success']));
        $totalDuration = array_sum(array_column($results, 'duration_ms'));

        $summary = [
            'orchestration_id' => $orchestrationId,
            'user' => $user,
            'mode' => 'sync',
            'total_requests' => $totalRequests,
            'successful_requests' => $successfulRequests,
            'failed_requests' => $totalRequests - $successfulRequests,
            'success_rate' => $totalRequests > 0 ? round(($successfulRequests / $totalRequests) * 100, 2) : 0,
            'total_duration_ms' => round($totalDuration, 2),
            'average_duration_ms' => $totalRequests > 0 ? round($totalDuration / $totalRequests, 2) : 0,
            'timestamp' => now()->toISOString()
        ];

        Log::info("API Orchestration completed (sync)", $summary);

        return response()->json([
            'success' => true,
            'message' => 'API orchestration completed',
            'summary' => $summary,
            'results' => $results
        ]);
    }

    /**
     * Get orchestration status
     */
    public function status($orchestrationId)
    {
        // Check cache for completed orchestration
        $result = Cache::get("orchestration_result_{$orchestrationId}");
        
        if ($result) {
            return response()->json([
                'success' => true,
                'status' => 'completed',
                'orchestration_id' => $orchestrationId,
                'result' => $result
            ]);
        }

        // Check if job is still in queue or processing
        $queuedJob = \DB::table('jobs')
            ->where('payload', 'like', "%{$orchestrationId}%")
            ->first();

        if ($queuedJob) {
            return response()->json([
                'success' => true,
                'status' => 'processing',
                'orchestration_id' => $orchestrationId,
                'queue_info' => [
                    'queue' => $queuedJob->queue,
                    'attempts' => $queuedJob->attempts,
                    'created_at' => date('Y-m-d H:i:s', $queuedJob->created_at),
                    'available_at' => date('Y-m-d H:i:s', $queuedJob->available_at)
                ]
            ]);
        }

        // Check failed jobs
        $failedJob = \DB::table('failed_jobs')
            ->where('payload', 'like', "%{$orchestrationId}%")
            ->first();

        if ($failedJob) {
            return response()->json([
                'success' => false,
                'status' => 'failed',
                'orchestration_id' => $orchestrationId,
                'error' => json_decode($failedJob->exception, true)['message'] ?? 'Unknown error',
                'failed_at' => $failedJob->failed_at
            ]);
        }

        return response()->json([
            'success' => false,
            'status' => 'not_found',
            'orchestration_id' => $orchestrationId,
            'message' => 'Orchestration not found'
        ], 404);
    }

    /**
     * Cancel a queued orchestration
     */
    public function cancel($orchestrationId)
    {
        try {
            // Remove from queue if still queued
            $deleted = \DB::table('jobs')
                ->where('payload', 'like', "%{$orchestrationId}%")
                ->delete();

            if ($deleted > 0) {
                Log::info("Orchestration cancelled", ['orchestration_id' => $orchestrationId]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Orchestration cancelled successfully',
                    'orchestration_id' => $orchestrationId
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Orchestration not found in queue or already processing',
                'orchestration_id' => $orchestrationId
            ], 404);

        } catch (\Exception $e) {
            Log::error("Failed to cancel orchestration", [
                'orchestration_id' => $orchestrationId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel orchestration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function logRequest($api, $result, $user, $orchestrationId = null)
    {
        try {
            // Only log if RequestLog model exists and table is available
            if (class_exists('App\\Models\\RequestLog')) {
                RequestLog::create([
                    'orchestration_id' => $orchestrationId,
                    'user' => $user,
                    'url' => $api['url'],
                    'method' => $api['method'],
                    'request_payload' => $api,
                    'response_payload' => $result,
                    'status_code' => $result['status_code'],
                    'duration_ms' => $result['duration_ms'],
                    'success' => $result['success']
                ]);
            }
        } catch (\Exception $e) {
            // Silently fail if logging doesn't work - don't break the main flow
            Log::warning("Failed to log request: " . $e->getMessage());
        }
    }
}
