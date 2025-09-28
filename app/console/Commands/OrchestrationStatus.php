<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OrchestrationStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orchestration:status 
                            {orchestration_id? : Specific orchestration ID to check}
                            {--recent=10 : Number of recent orchestrations to show}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the status of orchestration jobs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $orchestrationId = $this->argument('orchestration_id');
        $recent = $this->option('recent');

        if ($orchestrationId) {
            $this->showSpecificOrchestration($orchestrationId);
        } else {
            $this->showRecentOrchestrations($recent);
        }
    }

    /**
     * Show status of a specific orchestration
     */
    private function showSpecificOrchestration($orchestrationId)
    {
        $this->info("Checking orchestration: {$orchestrationId}");

        // Check cache for completed orchestration
        $result = Cache::get("orchestration_result_{$orchestrationId}");
        
        if ($result) {
            $this->info("Status: Completed");
            $this->table(
                ['Metric', 'Value'],
                [
                    ['User', $result['user']],
                    ['Total Requests', $result['total_requests']],
                    ['Successful', $result['successful_requests']],
                    ['Failed', $result['failed_requests']],
                    ['Success Rate', $result['success_rate'] . '%'],
                    ['Total Duration', $result['total_duration_ms'] . 'ms'],
                    ['Average Duration', $result['average_duration_ms'] . 'ms'],
                    ['Completed At', $result['timestamp']],
                ]
            );

            if ($this->confirm('Show detailed results?')) {
                $this->showDetailedResults($result['results']);
            }
        } else {
            // Check if job is still in queue
            $queuedJob = DB::table('jobs')
                ->where('payload', 'like', "%{$orchestrationId}%")
                ->first();

            if ($queuedJob) {
                $this->info("Status: Queued");
                $this->info("Queue: {$queuedJob->queue}");
                $this->info("Attempts: {$queuedJob->attempts}");
                $this->info("Created: " . date('Y-m-d H:i:s', $queuedJob->created_at));
            } else {
                $this->error("Orchestration not found: {$orchestrationId}");
            }
        }
    }

    /**
     * Show recent orchestrations
     */
    private function showRecentOrchestrations($limit)
    {
        $this->info("Recent orchestrations (limit: {$limit}):");

        // Get recent orchestrations from request logs
        $recentOrchestrations = DB::table('request_logs')
            ->select('orchestration_id', 'user', 'created_at')
            ->whereNotNull('orchestration_id')
            ->groupBy('orchestration_id', 'user', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        if ($recentOrchestrations->isEmpty()) {
            $this->info("No recent orchestrations found.");
            return;
        }

        $tableData = [];
        foreach ($recentOrchestrations as $orchestration) {
            $result = Cache::get("orchestration_result_{$orchestration->orchestration_id}");
            $status = $result ? 'Completed' : 'Processing/Failed';
            
            $tableData[] = [
                $orchestration->orchestration_id,
                $orchestration->user,
                $status,
                $result ? $result['success_rate'] . '%' : 'N/A',
                date('Y-m-d H:i:s', strtotime($orchestration->created_at))
            ];
        }

        $this->table(
            ['Orchestration ID', 'User', 'Status', 'Success Rate', 'Started At'],
            $tableData
        );
    }

    /**
     * Show detailed results for an orchestration
     */
    private function showDetailedResults($results)
    {
        $tableData = [];
        foreach ($results as $result) {
            $tableData[] = [
                $result['index'],
                $result['method'],
                $result['url'],
                $result['status_code'],
                $result['success'] ? 'Yes' : 'No',
                $result['duration_ms'] . 'ms',
                isset($result['error']) ? substr($result['error'], 0, 50) . '...' : 'None'
            ];
        }

        $this->table(
            ['Index', 'Method', 'URL', 'Status', 'Success', 'Duration', 'Error'],
            $tableData
        );
    }
}
