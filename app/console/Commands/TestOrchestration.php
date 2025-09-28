<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\OrchestrationJob;
use App\Services\TransformationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TestOrchestration extends Command
{
    protected $signature = 'orchestration:test {--sync : Run synchronously without queue}';
    protected $description = 'Test the orchestration system with sample APIs';

    public function handle()
    {
        $this->info('🚀 Testing Dynamic API Orchestration System');
        $this->newLine();

        // Sample test APIs
        $testApis = [
            [
                'url' => 'https://jsonplaceholder.typicode.com/posts/1',
                'method' => 'GET',
                'headers' => ['Accept' => 'application/json'],
                'transformations' => [
                    'request' => [],
                    'response' => ['extract' => ['title', 'body']]
                ]
            ],
            [
                'url' => 'https://httpbin.org/get',
                'method' => 'GET',
                'conditions' => [
                    ['type' => 'success', 'api_index' => 0]
                ]
            ]
        ];

        $orchestrationId = 'test_' . uniqid();
        $userId = 'test-user-' . time();

        if ($this->option('sync')) {
            $this->info('Running synchronous test...');
            $job = new OrchestrationJob($userId, $testApis, $orchestrationId);
            $transformationService = new TransformationService();
            $job->handle($transformationService);
            
            $this->checkResults($orchestrationId);
        } else {
            $this->info('Dispatching to queue...');
            OrchestrationJob::dispatch($userId, $testApis, $orchestrationId);
            
            $this->info("✅ Job dispatched with ID: {$orchestrationId}");
            $this->info("👀 Monitor with: php artisan queue:work --queue=orchestration --verbose");
            $this->newLine();
            
            $this->info('Waiting 5 seconds for processing...');
            sleep(5);
            
            $this->checkResults($orchestrationId);
        }
    }

    private function checkResults($orchestrationId)
    {
        $this->newLine();
        $this->info('📊 Checking Results:');
        
        // Check cache
        $result = Cache::get("orchestration_result_{$orchestrationId}");
        if ($result) {
            $this->info("✅ Found cached result");
            $this->line("Status: " . ($result['status'] ?? 'completed'));
            $this->line("APIs processed: " . count($result['results']));
            $this->line("Total duration: " . ($result['total_duration_ms'] ?? 'N/A') . 'ms');
            $this->line("Success rate: " . ($result['success_rate'] ?? 'N/A') . '%');
        } else {
            $this->warn("⚠️  No cached result found yet");
        }
        
        // Check logs
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            $logs = file_get_contents($logFile);
            $orchestrationLogs = array_filter(
                explode("\n", $logs),
                fn($line) => strpos($line, $orchestrationId) !== false
            );
            
            if (!empty($orchestrationLogs)) {
                $this->info("📝 Found " . count($orchestrationLogs) . " log entries");
                $this->line("Latest: " . end($orchestrationLogs));
            }
        }
    }
}
