<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\RequestLog;
use App\Services\TransformationService;

class OrchestrationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    protected $apis;
    protected $orchestrationId;
    protected $options;

    /**
     * Create a new job instance.
     */
    public function __construct($user, $apis, $orchestrationId = null, $options = [])
    {
        $this->user = $user;
        $this->apis = $apis;
        $this->orchestrationId = $orchestrationId ?? uniqid('orch_');
        $this->options = $options;
    }

    /**
     * Execute the job.
     */
    public function handle(TransformationService $transformationService): void
    {
        Log::info("Orchestration job started", [
            'orchestration_id' => $this->orchestrationId,
            'user' => $this->user,
            'apis_count' => count($this->apis)
        ]);

        $results = [];
        $previousResults = []; // Store results for conditional logic

        foreach ($this->apis as $index => $api) {
            $startTime = microtime(true);
            
            try {
                if (!$this->shouldExecuteApi($api, $previousResults)) {
                    Log::info("Skipping API due to conditional logic", [
                        'orchestration_id' => $this->orchestrationId,
                        'api_index' => $index,
                        'url' => $api['url']
                    ]);
                    continue;
                }

                $transformedApi = $transformationService->transformRequest($api, $previousResults);
                
                // Prepare HTTP client with timeout
                $timeout = $transformedApi['timeout'] ?? 30;
                $httpClient = Http::timeout($timeout);
                
                // Add headers if provided
                if (isset($transformedApi['headers']) && is_array($transformedApi['headers'])) {
                    $httpClient = $httpClient->withHeaders($transformedApi['headers']);
                }

                // Make the API call based on method
                $method = strtoupper($transformedApi['method']);
                $response = match($method) {
                    'GET' => $httpClient->get($transformedApi['url']),
                    'POST' => $httpClient->post($transformedApi['url'], $transformedApi['body'] ?? []),
                    'PUT' => $httpClient->put($transformedApi['url'], $transformedApi['body'] ?? []),
                    'DELETE' => $httpClient->delete($transformedApi['url']),
                    'PATCH' => $httpClient->patch($transformedApi['url'], $transformedApi['body'] ?? []),
                    default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}")
                };

                $endTime = microtime(true);
                $duration = round(($endTime - $startTime) * 1000, 2);

                $rawResult = [
                    'index' => $index,
                    'url' => $transformedApi['url'],
                    'method' => $method,
                    'status_code' => $response->status(),
                    'success' => $response->successful(),
                    'duration_ms' => $duration,
                    'response_size' => strlen($response->body()),
                    'data' => $response->json() ?? $response->body()
                ];

                $result = $transformationService->transformResponse($rawResult, $transformedApi);
                
                // Log successful request
                $this->logRequest($transformedApi, $result);
                
                $results[] = $result;
                $previousResults[] = $result; // Store for next API's conditional logic

            } catch (\Exception $e) {
                $endTime = microtime(true);
                $duration = round(($endTime - $startTime) * 1000, 2);

                $result = [
                    'index' => $index,
                    'url' => $api['url'] ?? 'unknown',
                    'method' => $api['method'] ?? 'unknown',
                    'status_code' => 0,
                    'success' => false,
                    'duration_ms' => $duration,
                    'error' => 'Orchestration error: ' . $e->getMessage(),
                    'data' => null
                ];

                Log::error("API Orchestration error", [
                    'orchestration_id' => $this->orchestrationId,
                    'api_index' => $index,
                    'error' => $e->getMessage()
                ]);

                $this->logRequest($api, $result);
                $results[] = $result;
                $previousResults[] = $result;

                if ($this->options['stop_on_failure'] ?? false) {
                    Log::info("Stopping orchestration due to failure", [
                        'orchestration_id' => $this->orchestrationId
                    ]);
                    break;
                }
            }
        }

        // Calculate summary statistics
        $totalRequests = count($results);
        $successfulRequests = count(array_filter($results, fn($r) => $r['success']));
        $totalDuration = array_sum(array_column($results, 'duration_ms'));

        $summary = [
            'orchestration_id' => $this->orchestrationId,
            'user' => $this->user,
            'total_requests' => $totalRequests,
            'successful_requests' => $successfulRequests,
            'failed_requests' => $totalRequests - $successfulRequests,
            'success_rate' => $totalRequests > 0 ? round(($successfulRequests / $totalRequests) * 100, 2) : 0,
            'total_duration_ms' => round($totalDuration, 2),
            'average_duration_ms' => $totalRequests > 0 ? round($totalDuration / $totalRequests, 2) : 0,
            'timestamp' => now()->toISOString(),
            'results' => $results
        ];

        Log::info("Orchestration job completed", $summary);

        cache()->put("orchestration_result_{$this->orchestrationId}", $summary, 3600); // Store for 1 hour
    }

    /**
     * Check if API should be executed based on conditional logic
     */
    private function shouldExecuteApi($api, $previousResults): bool
    {
        // If no conditions specified, always execute
        if (!isset($api['conditions']) || empty($api['conditions'])) {
            return true;
        }

        foreach ($api['conditions'] as $condition) {
            $type = $condition['type'] ?? 'success';
            $apiIndex = $condition['api_index'] ?? -1;

            // Check if referenced API exists in previous results
            if ($apiIndex >= 0 && isset($previousResults[$apiIndex])) {
                $referencedResult = $previousResults[$apiIndex];
                
                switch ($type) {
                    case 'success':
                        if (!$referencedResult['success']) {
                            return false;
                        }
                        break;
                    case 'failure':
                        if ($referencedResult['success']) {
                            return false;
                        }
                        break;
                    case 'status_code':
                        $expectedCode = $condition['value'] ?? 200;
                        if ($referencedResult['status_code'] !== $expectedCode) {
                            return false;
                        }
                        break;
                }
            }
        }

        return true;
    }

    /**
     * Log the API request
     */
    private function logRequest($api, $result): void
    {
        try {
            if (class_exists('App\\Models\\RequestLog')) {
                RequestLog::create([
                    'orchestration_id' => $this->orchestrationId,
                    'user' => $this->user,
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
            Log::warning("Failed to log orchestration request: " . $e->getMessage());
        }
    }
}
