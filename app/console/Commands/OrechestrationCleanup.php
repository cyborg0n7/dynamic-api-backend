<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OrchestrationCleanup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orchestration:cleanup 
                            {--days=7 : Delete logs older than this many days}
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old orchestration logs and cache entries';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $dryRun = $this->option('dry-run');
        $cutoffDate = Carbon::now()->subDays($days);

        $this->info("Cleaning up orchestration data older than {$days} days (before {$cutoffDate->format('Y-m-d H:i:s')})");

        if ($dryRun) {
            $this->warn("DRY RUN MODE - No data will be deleted");
        }

        // Clean up request logs
        $this->cleanupRequestLogs($cutoffDate, $dryRun);

        // Clean up failed jobs
        $this->cleanupFailedJobs($cutoffDate, $dryRun);

        // Clean up cache entries (this is more complex as we need to find orchestration cache keys)
        $this->cleanupCacheEntries($dryRun);

        $this->info("Cleanup completed!");
    }

    /**
     * Clean up old request logs
     */
    private function cleanupRequestLogs($cutoffDate, $dryRun)
    {
        $query = DB::table('request_logs')
            ->where('created_at', '<', $cutoffDate)
            ->whereNotNull('orchestration_id');

        $count = $query->count();

        if ($count > 0) {
            $this->info("Found {$count} old request log entries");
            
            if (!$dryRun) {
                $deleted = $query->delete();
                $this->info("Deleted {$deleted} request log entries");
            }
        } else {
            $this->info("No old request log entries found");
        }
    }

    /**
     * Clean up old failed jobs
     */
    private function cleanupFailedJobs($cutoffDate, $dryRun)
    {
        $query = DB::table('failed_jobs')
            ->where('failed_at', '<', $cutoffDate)
            ->where('payload', 'like', '%OrchestrationJob%');

        $count = $query->count();

        if ($count > 0) {
            $this->info("Found {$count} old failed orchestration jobs");
            
            if (!$dryRun) {
                $deleted = $query->delete();
                $this->info("Deleted {$deleted} failed job entries");
            }
        } else {
            $this->info("No old failed orchestration jobs found");
        }
    }

    /**
     * Clean up cache entries
     */
    private function cleanupCacheEntries($dryRun)
    {
        // Get orchestration IDs from old request logs to clean their cache
        $oldOrchestrationIds = DB::table('request_logs')
            ->select('orchestration_id')
            ->whereNotNull('orchestration_id')
            ->where('created_at', '<', Carbon::now()->subHours(1)) // Cache entries older than 1 hour
            ->distinct()
            ->pluck('orchestration_id');

        if ($oldOrchestrationIds->isNotEmpty()) {
            $this->info("Found {$oldOrchestrationIds->count()} orchestration cache entries to clean");
            
            if (!$dryRun) {
                $cleaned = 0;
                foreach ($oldOrchestrationIds as $orchestrationId) {
                    if (Cache::forget("orchestration_result_{$orchestrationId}")) {
                        $cleaned++;
                    }
                }
                $this->info("Cleaned {$cleaned} cache entries");
            }
        } else {
            $this->info("No old cache entries found");
        }
    }
}
