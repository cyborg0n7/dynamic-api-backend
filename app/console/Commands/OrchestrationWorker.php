<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use App\Jobs\OrchestrationJob;

class OrchestrationWorker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orchestration:work 
                            {--queue=orchestration : The queue to work}
                            {--timeout=60 : The timeout for each job}
                            {--memory=128 : The memory limit in megabytes}
                            {--sleep=3 : Number of seconds to sleep when no job is available}
                            {--tries=3 : Number of times to attempt a job before logging it failed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start processing orchestration jobs from the queue';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $queue = $this->option('queue');
        $timeout = $this->option('timeout');
        $memory = $this->option('memory');
        $sleep = $this->option('sleep');
        $tries = $this->option('tries');

        $this->info("Starting Orchestration Worker...");
        $this->info("Queue: {$queue}");
        $this->info("Timeout: {$timeout}s");
        $this->info("Memory Limit: {$memory}MB");
        $this->info("Sleep: {$sleep}s");
        $this->info("Max Tries: {$tries}");

        Log::info("Orchestration worker started", [
            'queue' => $queue,
            'timeout' => $timeout,
            'memory' => $memory,
            'sleep' => $sleep,
            'tries' => $tries
        ]);

        // Start the queue worker
        $this->call('queue:work', [
            '--queue' => $queue,
            '--timeout' => $timeout,
            '--memory' => $memory,
            '--sleep' => $sleep,
            '--tries' => $tries,
            '--verbose' => true,
        ]);
    }
}
